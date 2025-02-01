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
        foreach ($data as $key => $value) {
            $website->$key = $value;
        }
        if ($website->save()) {

            // Create a directory by name of directory
            $directoryPath = $website->directory;
            if (!file_exists($directoryPath)) {
                mkdir($directoryPath, 0755, true);
            }

            // Prepare the apache2 conf file
            $confContent = "
<VirtualHost *:{$website->port}>
    DocumentRoot /var/www/{$website->document_root}
    ServerName {$website->server_name}
    ServerAlias {$website->server_alias}
    <Directory /var/www/{$website->directory}>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
RewriteEngine on
RewriteCond %{SERVER_NAME} =www.{$website->domain_name} [OR]
RewriteCond %{SERVER_NAME} ={$website->domain_name}
RewriteRule  ^ https://%{SERVER_NAME}%{REQUEST_URI} [END,NE,R=permanent]
</VirtualHost>
";
            $confFilePath = "/etc/apache2/sites-available/{$website->domain_name}.conf";
            file_put_contents($confFilePath, $confContent);

            // Copy the conf file to apache2 sites-available
            // This step is already done by file_put_contents above

            // Enable the site
            shell_exec("a2ensite {$website->domain_name}.conf");

            // Reload apache2 to apply changes
            shell_exec("systemctl reload apache2");
        }
        return redirect()->route('dashboard');
    }
}
