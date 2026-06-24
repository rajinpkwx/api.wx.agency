<?php

namespace app\Http\Controllers\Gx;

use app\Http\Controllers\Controller;
use Illuminate\Http\Request;
use app\Models\Gx\Kirim;
use App\Service\Gx\KirimService;

use Artisan;
use Session;

class KirimController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(KirimService $kirim)
    {
        $contacts = Kirim::all();
        return view('Gx.Kirim.list',compact('contacts'));
    }
    public function getKirimContacts()
    {
        return $contacts = Kirim::all();
    }





    public function sync()
    {

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $email
     * @return \Illuminate\Http\Response
     */
    public function destroy(KirimService $kirim ,$email)
    {

        $result = $kirim->deleteEmail($email);
        dd($result);


    }
}
