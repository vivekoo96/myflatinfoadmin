<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use \App\Models\Visitor;
use \App\Models\Notification;
use \Log;
class VisitorExpired extends Command
{

    protected $signature = 'visitor:expired';

    protected $description = 'Command description';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $visitors = Visitor::where('stay_to', '<', now())->where('status','Inactive')->get();
        
       
        foreach($visitors as $visitor){
            $visitor->status = 'Expired';
            $visitor->save();
        }
    }
}
