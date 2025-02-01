<?php

namespace App\Http\Controllers;

use App\Models\Website;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        $websites = Website::all();
        return view('admin.index', ['websites' => $websites]);
    }
    public function addWebsite(Request $request)
    {
        return view('admin.addWebsite');
    }
    public function storeWebsite(Request $request)
    {
        $data = $request->all();
        unset($data['_token']);
        $validated = $request->validate([
            'name' => 'required',
            'domain_name' => 'required',
            'document_root' => 'required',
            'server_name' => 'required',
            'server_alias' => 'required',
            'directory' => 'required',
            'port' => 'required',
            'status' => 'required',
        ]);
        $website = new Website();
        foreach($data as $key => $value){
            $website->$key = $value;
        }
        $website->save();
        system('ls -l');
        return redirect()->route('dashboard');
    }
}
