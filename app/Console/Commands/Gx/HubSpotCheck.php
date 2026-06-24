<?php

namespace App\Console\Commands\Gx;

use Illuminate\Console\Command;
use App\Models\Gx\Kirim;
use App\Service\Gx\KirimService;


class HubSpotCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hubspot:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check delete from HubSpot from HubSpot and delete from Kirim';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(KirimService $kirim)
    {

        $kirimEmails = Kirim::all();
        $bar = $this->output->createProgressBar(Kirim::where('status',0)->count());
        $bar->start();
         foreach($kirimEmails as $email)
         {

            $bar->advance();
            try
            {
            $query['hapikey'] = '133c6406-8fea-4358-a36a-e78a1555336c';
            $client = new \GuzzleHttp\Client();
            $request = $client->get('https://api.hubapi.com/contacts/v1/contact/email/'.$email->email.'/profile', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-type' => 'application/x-www-form-urlencoded'
                    ],
                'query' => $query,

            ]);


            $response = $request->getBody()->getContents();
            $json = json_decode($response, true);

            } catch (\Exception $e) {

            $response = ['status' => false, 'message' => $e->getMessage()];

            }

            if (array_key_exists('unsubscribe_from_kirim', $json['properties'])) {

                if($json['properties']['unsubscribe_from_kirim']['value']=='true')
                {
                    $kirim->deleteEmail($email);
                    Kirim::where('email',$email->email)->update([
                        'status'=>'false'
                    ]);
                }
                else
                {
                    Kirim::where('email',$email->email)->update([
                        'status'=>'true'
                    ]);
                }
            }
        }
        $bar->finish();
        $this->info('All sorted contacs are synced');
    }
}
