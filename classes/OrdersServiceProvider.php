<?php

namespace Ecjia\App\Orders;

use Royalcms\Component\App\AppServiceProvider;

class OrdersServiceProvider extends  AppServiceProvider
{
    
    public function boot()
    {
        $this->package('ecjia/app-orders');
    }
    
    public function register()
    {
        
    }
    
    
    
}