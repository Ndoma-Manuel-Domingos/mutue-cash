<?php

namespace App\Services\Api;

class ApiService
{
  public function baseUrlProduction()
  {
    return 'mutue.co.ao/mutue';
  }
  

  public function baseUrlDevelopment()
  {
    //return '10.10.6.32:8080/mutue';
    //return '10.10.6.13:8080/mutue';
    return 'mutue.co.ao/mutue';
  }

  public function baseUrl()
  {
    return env('APP_ENV') === 'production' ? $this->baseUrlProduction() : $this->baseUrlDevelopment();
  }

  public function teste()
  {
    dd('teste');
    
  }

  

}
