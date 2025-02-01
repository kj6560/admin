<?php

namespace App\Http\Controllers;

use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        try {
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

            Log::info("Validation passed", $validated);

            $website = new Website();
            foreach ($data as $key => $value) {
                $website->$key = $value;
            }

            if ($website->save()) {
                Log::info("Website saved successfully: ", ['id' => $website->id]);

                // Create a directory by name of directory
                $directoryPath = "/var/www/" . $website->document_root;
                if (!file_exists($directoryPath)) {
                    $mkdirCommand = "mkdir -p {$directoryPath} && chown www-data:www-data {$directoryPath} && chmod 755 {$directoryPath}";
                    Log::info("Executing: " . $mkdirCommand);

                    $output = shell_exec($mkdirCommand . " 2>&1"); // Capture errors
                    Log::info("mkdir output: " . $output);

                    if (!file_exists($directoryPath)) {
                        Log::error("Directory was not created: " . $directoryPath);
                    }
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
    RewriteRule ^ https://%{SERVER_NAME}%{REQUEST_URI} [END,NE,R=permanent]
</VirtualHost>
";

                $confFilePath = "/etc/apache2/sites-available/{$website->domain_name}.conf";
                if (!file_put_contents($confFilePath, $confContent)) {
                    Log::error("Failed to write Apache config file: " . $confFilePath);
                    return redirect()->back()->with('error', 'Failed to create Apache config file.');
                }

                Log::info("Apache config file created at " . $confFilePath);

                // Enable the site
                $enableSiteCommand = "a2ensite {$website->domain_name}.conf";
                Log::info("Executing: " . $enableSiteCommand);
                shell_exec($enableSiteCommand);

                // Reload Apache
                $reloadApacheCommand = "systemctl reload apache2";
                Log::info("Executing: " . $reloadApacheCommand);
                shell_exec($reloadApacheCommand);

                return redirect()->route('dashboard')->with('success', 'Website created successfully.');
            }
        } catch (\Exception $e) {
            Log::error("Error in storeWebsite function: " . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }
}
