<?php

class Menu {
    private $menu = [];
    
    private $site_info = [];

    private $role_auth = [
        'cashier' => [1],
        'manager' => [2,3],
        'admin' => [1,2,3,4,5,6]
    ];

    public function __construct( $site_info ){
        $this->site_info = $site_info;

        $this->menu = [
            1 => [
                'title' => 'POS',
                'route' => '/',
                'icon' => 'fas fa-calculator'
            ],
            2 => [
                'title' => 'Stocks',
                'route' => '/stocks',
                'icon' => 'fas fa-list',
            ],
            3 => [
                'title' => 'Products',
                'route' => '/products',
                'icon' => 'fas fa-boxes'
            ],
            4 => [
                'title' => 'Users',
                'route' => '/users',
                'icon' => 'fas fa-users'
            ],
            5 => [
                'title' => 'Reports',
                'route' => '/reports',
                'icon' => 'fas fa-print'
            ],
            6 => [
                'title' => 'Logs',
                'route' => '/logs',
                'icon' => 'fas fa-clipboard-list'
            ]
        ];
    }

    public function getMenu( $role ) {
        $items = [];

        foreach( $this->role_auth[$role] as $menu_item ) {
            $items[] = $this->menu[$menu_item];
        }

        return $items;
    }
}