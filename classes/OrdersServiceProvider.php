<?php

namespace Ecjia\App\Orders;

use Royalcms\Component\App\AppParentServiceProvider;

class OrdersServiceProvider extends  AppParentServiceProvider
{
    
    public function boot()
    {
        $this->package('ecjia/app-orders', null, dirname(__DIR__));
    }
    
    public function register()
    {
        
    }
    
    
    
}