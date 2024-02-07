<?php

class Menu {
    private $menu = [];
    
    private $site_info = [];

    private $role_auth = [
        'cashier' => [0,1,9],
        'manager' => [3,4,10],
        'admin' => [0,1,2,3,4,5,7,8,9,10,11],
        'super' => [0,1,2,3,4,5,6,7,8,9,10,11]
    ];

    public $redirects = [
        'cashier' => '/',
        'manager' => '/stocks',
        'admin' => '/orders',
        'super' => '/settings'
    ];

    public function __construct( $site_info ){
        $this->site_info = $site_info;

        $this->menu = [
            0 => [
                'route' => '/scanner',
                'title' => -1,
                'icon' => -1
            ],
            1 => [
                'route' => '/',
                'title' => 'POS',
                'icon' => 'fas fa-calculator'
            ],
            2 => [
                'route' => '/orders',
                'title' => 'Past Orders',
                'icon' => 'fas fa-clock'
            ],
            3 => [
                'route' => '/stocks',
                'title' => 'Stocks',
                'icon' => 'fas fa-list',
            ],
            4 => [
                'route' => '/products',
                'title' => 'Products',
                'icon' => 'fas fa-boxes'
            ],
            5 => [
                'route' => '/users',
                'title' => 'Users',
                'icon' => 'fas fa-users'
            ],
            6 => [
                'route' => '/logs',
                'title' => 'Logs',
                'icon' => 'fas fa-clipboard-list'
            ],
            7 => [
                'route' => '/settings',
                'title' => 'Settings',
                'icon' => 'fas fa-cog'
            ],
            8 => [
                'route' => '/delete/users',
                'title' => -1,
                'icon' => -1
            ],
            9 => [
                'route' => '/verifyPurchase',
                'title' => -1,
                'icon' => -1
            ],
            10 => [
                'route' => '/getAlert',
                'title' => -1,
                'icon' => -1
            ],
            11 => [
                'route' => '/analytics',
                'title' => 'Analytics',
                'icon' => 'fas fa-chart-bar'
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