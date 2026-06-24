<?php

namespace App\Console\Commands\Gx;

use Illuminate\Console\Command;
use App\Models\Gx\Kirim;
use App\Service\Gx\KirimService;

use Session;

class KirimSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kirim:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all kirim contacts';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(KirimService $kirim)
    {
        parent::__construct();
        $this->kirim = $kirim;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->syncKirim($this->kirim);
    }

    public function syncKirim()
    {

            foreach($this->kirim->getKirimList() as $list)
            {

                Session::put('offset',0);
                while(Session::get('offset')!=-1)
                {
                $time = time();
                $generated_token = hash_hmac("sha256","globalexpansion_en"."::"."lysW2r6P8Agq59dkaQhFSVfbEw3LcDxtglobalexpansion_en"."::".$time,"lysW2r6P8Agq59dkaQhFSVfbEw3LcDxtglobalexpansion_en");
                try {
                $client = new \GuzzleHttp\Client();
                            $request = $client->get('https://api.kirim.email/v3/subscriber', [
                                'headers' => [
                                    'Accept' => 'application/json',
                                    'Content-Type'=> 'application/json',
                                    'Auth-Id'=> 'globalexpansion_en',
                                    'Auth-Token'=> $generated_token,
                                    'Timestamp'=> $time,
                                    'Offset'=>(int)Session::get('offset'),
                                    'List-Id'=>$list['id']
                                ]
                            ]);
                    $response = $request->getBody()->getContents();
                    $json = json_decode($response, true);
                    } catch (\Exception $e)
                    {
                    $response = ['status' => false, 'message' => $e->getMessage()];
                    }

                    if ($json['offset']<$json['total'])
                    {
                        $offset = $json['offset'] + 100;
                        Session::put('offset',$offset);
                    }
                    else
                    {
                        Session::put('offset',-1);
                    }

                    foreach($json['data'] as $jsons)
                    {
                        echo $jsons['email']; echo PHP_EOL;
                        if(Kirim::where('email',$jsons['email'])->exists()==false)
                        {
                            Kirim::insert(
                            [
                                'kirim_id'=>$jsons['id'],
                                'name'=>$jsons['name'],
                                'email'=>$jsons['email'],
                                'status'=>0
                            ]);
                        }else
                        {
                            Kirim::where('email',$jsons['email'])->increment('count',1);
                        }
                    }
                }

            }

            $this->info('All Kirim email synced');
    }


}
