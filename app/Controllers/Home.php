<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        $site = config('PublicSite');
        $products=$site->products; $services=$site->services; $faqs=$site->faqs; $bookingReady=false; $catalogLive=false; $catalogError=false; $contacts=[];
        try {
            $db=db_connect();
            if($db->tableExists('products') && $db->tableExists('services')) {
                $icons=['Solar panels'=>'panel','Inverters'=>'inverter','Batteries'=>'battery','Mounting equipment'=>'mount','Wires'=>'wire'];
                $published=$db->table('products')->where('active',1)->orderBy('id','DESC')->get()->getResultArray();
                if($published) {
                    $catalogLive=true; $products=[];
                    foreach($published as $p) {
                        $price=$p['price']!==null?'PHP '.number_format((float)$p['price'],2):'Price to be confirmed';
                        $products[]=['name'=>$p['name'],'icon'=>$icons[$p['category']]??'panel','tag'=>$p['category'],'description'=>$p['description'],'details'=>$price.'. '.($p['stock']!==null?'Stock: '.(int)$p['stock'].'.':'Contact the company for availability.')];
                    }
                }
                $services=[]; $serviceIcons=array_column($site->services,'icon','name');
                foreach($db->table('services')->where('active',1)->orderBy('id')->get()->getResultArray() as $s) {
                    $services[]=['name'=>$s['name'],'icon'=>$serviceIcons[$s['name']]??'sun','description'=>$s['description'],'details'=>$s['price']!==null?'Listed price: PHP '.number_format((float)$s['price'],2).'. Final scope must be confirmed.':'Pricing and final scope will be confirmed after review.'];
                }
                $bookingReady=(new \App\Libraries\Management($db))->ready();
                foreach($db->table('settings')->get()->getResultArray() as $setting) { $contacts[$setting['setting_key']]=$setting['value']; }
                foreach($faqs as &$faq) {
                    if(str_contains($faq['question'],'What products')) { $faq['answer']=$catalogLive?'Published products above show the company’s current catalog entries. Pricing and stock reflect recorded information and should be confirmed before purchase.':$faq['answer']; }
                    if(str_contains($faq['question'],'request a consultation')) { $faq['answer']='Create an account or sign in, then open Request a service. Select a service, preferred time and service address. The company reviews requests before confirming a schedule.'; }
                    if(str_contains($faq['question'],'manage a booking')) { $faq['answer']='Sign in to view your bookings, notifications, service reports and recorded payments. Pending or scheduled requests can be cancelled or submitted for rescheduling before work begins.'; }
                    if(str_contains($faq['question'],'Where is the company') && array_filter($contacts)) { $faq['answer']='Sun Son Solar is based in Pasig City, Philippines. See the contact section below for the official contact details currently published by the company.'; }
                } unset($faq);
            }
        } catch(\Throwable $e) { $catalogError=true; }
        return view('home',compact('products','services','faqs','bookingReady','catalogLive','catalogError','contacts'));
    }
}
