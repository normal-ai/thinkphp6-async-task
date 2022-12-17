<?php
namespace cathy\AsyncTask;
use think\Service as BaseService;

class Service extends BaseService{
    public function register(){
        $this->commands([
            'worker:task'=>'\\AsyncTask\\command\\AsyncTask',
        ]);
    }
}