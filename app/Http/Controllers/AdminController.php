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

                // Create the website directory
                $directoryPath = "/var/www/" . $website->document_root;
                if (!file_exists($directoryPath)) {
                    $mkdirCommand = "mkdir -p {$directoryPath} && sudo chown www-data:www-data {$directoryPath} && sudo chmod 755 {$directoryPath}";
                    Log::info("Executing: " . $mkdirCommand);
                    $mkdirOutput = shell_exec($mkdirCommand . " 2>&1");
                    Log::info("mkdir output: " . $mkdirOutput);
                }

                // Prepare Apache configuration
                                $confContent = "
                <VirtualHost *:80>
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
                file_put_contents($confFilePath, $confContent);
                Log::info("Apache config file created at " . $confFilePath);

                // Enable the site
                if (file_exists($confFilePath)) {
                    $a2ensiteCommand = "a2ensite {$website->domain_name}.conf 2>&1";
                    $a2ensiteOutput = shell_exec($a2ensiteCommand);
                    Log::info("a2ensite output: " . $a2ensiteOutput);
                } else {
                    Log::error("Apache config file not found: " . $confFilePath);
                    return redirect()->back()->with('error', 'Apache config file not found.');
                }

                // Reload Apache
                $apacheReloadCommand = "systemctl reload apache2 2>&1";
                $apacheReloadOutput = shell_exec($apacheReloadCommand);
                Log::info("Apache reload output: " . $apacheReloadOutput);

                // Check if SSL certificate already exists
                $sslCertPath = "/etc/letsencrypt/live/{$website->domain_name}/fullchain.pem";
                if (!file_exists($sslCertPath)) {
                    $certbotCommand = "certbot --apache -d {$website->domain_name} -d www.{$website->domain_name} --non-interactive --agree-tos --expand -m admin@{$website->domain_name} --redirect";
                    Log::info("Executing: " . $certbotCommand);
                    $certbotOutput = shell_exec($certbotCommand . " 2>&1");
                    Log::info("Certbot output: " . $certbotOutput);
                } else {
                    Log::info("SSL certificate already exists for {$website->domain_name}, skipping Certbot.");
                }

                // Final Apache reload
                $finalApacheReloadCommand = "sudo systemctl reload apache2 2>&1";
                $finalApacheReloadOutput = shell_exec($finalApacheReloadCommand);
                Log::info("Final Apache reload output: " . $finalApacheReloadOutput);

                return redirect()->route('dashboard')->with('success', 'Website created successfully with SSL.');
            }
        } catch (\Exception $e) {
            Log::error("Error in storeWebsite function: " . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }
}
