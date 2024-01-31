<?php

class Db {
    private $mysqli;
    public $per_page_record = 16;

    public function __construct( $hostname, $username, $password, $database ) {
        $this->mysqli = mysqli_connect( $hostname,$username,$password,$database );
    }

    public function escape( $str ) {
        return mysqli_real_escape_string($this->mysqli, $str);
    }

    public function error() {
        return $this->mysqli->error;
    }

    public function log( $file, $title, $description, $args = [] ) {
        $file = $this->escape($file);
        $title = $this->escape($title);
        $description = $this->escape($description);
        $user = isset($_SESSION['userid']) ? $_SESSION['userid'] : 'NULL';
        $args = $this->escape(json_encode($args));

        $sql = "INSERT INTO logs (user, file, title, log, args) VALUES ('$user', '$file', '$title', '$description', '$args')";
        $this->query($sql);
    }

    public function query( $sql ) {
        $query = $this->mysqli->query($sql);
        if($query) {
            if(stripos($sql, 'SELECT') === 0 ) return $query->fetch_assoc();
            elseif(stripos($sql, 'INSERT') === 0) return $this->mysqli->insert_id;
            return $this->mysqli->affected_rows;
        }

        return 0;
    }

    public function queryAll( $sql ) {
        $per_page_record = $this->per_page_record;
        $page = 1;
        if(isset($_GET['page'])) {
            $page = $this->escape( $_GET['page']);
        }

        $start_from = ($page-1) * $per_page_record;


        $query = $this->mysqli->query($sql." LIMIT $start_from, $per_page_record");
        $results = [];
        while($row = $query->fetch_assoc()) {
            $results[] = $row;
        }

        return $results;
    }

    function pagination( $table ) {
        $per_page_record = $this->per_page_record;
        $page = 1;
        if(isset($_GET['page'])) {
            $page = $this->escape($_GET['page']);
        }

        $sql = "SELECT COUNT(ID) FROM $table";
        $query = $this->mysqli->query($sql);
        $row = $query->fetch_assoc();
        $total_records = $row['COUNT(ID)'];
        $total_pages = ceil($total_records/$per_page_record);

        $pages = [];
        for($i = 1; $i <= $total_pages; $i ++) {
            $pages[] = [
                'class' => $i == $page ? 'active' : '',
                'index' => $i,
                'link' => '?page='.$i
            ];
        }
        return [
            'total_pages' => $total_pages,
            'prev_link' => $page > 1 ? '?page='.($page-1):'#',
            'prev_class' => $page > 1 ? '':'disabled',
            'next_link' => $page < $total_pages ? '?page='.($page+1):'#',
            'next_class' => $page < $total_pages ? '':'disabled',
            'pages' => $pages
        ];
    }

    private $user_details = [];

    public function getUser() {
        if(empty($this->user_details))
            $this->user_details = $this->fetchUserData();

        return $this->user_details;
    }

    public function clearUserData() {
        $this->user_details = [];
    }

    function fetchUserData() {
        if(!isset($_SESSION['userid']))
            return [];

        $expiry = base64_encode(strtotime(date('Y-m-d h:i:s'). ' + 10 days'));
        $sql = "UPDATE users SET expiry='$expiry' WHERE ID=$_SESSION[userid]";
        $sql = "SELECT ID, username, role, expiry FROM users WHERE ID=$_SESSION[userid]";
        return $this->query($sql);
    }

}