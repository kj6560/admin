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
        $data = $request->except('_token');

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

            $directoryPath = "/var/www/" . $website->document_root;
            $confFilePath = "/etc/apache2/sites-available/{$website->domain_name}.conf";

            // Remove previous configuration if it exists
            shell_exec("a2dissite {$website->domain_name}.conf 2>&1 && rm -f {$confFilePath}");
            Log::info("Old configuration removed (if existed)");

            // Create website directory if it doesn't exist
            if (!file_exists($directoryPath)) {
                $mkdirCommand = "mkdir -p {$directoryPath} && chown www-data:www-data {$directoryPath} && chmod 755 {$directoryPath}";
                shell_exec($mkdirCommand);
                Log::info("Website directory created: {$directoryPath}");
            }

            // Create Apache configuration file
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
            file_put_contents($confFilePath, $confContent);
            shell_exec("chmod 644 {$confFilePath}"); // Ensure correct permissions
            Log::info("Apache config file created at {$confFilePath}");

            // Enable the new site
            $enableSiteCommand = "a2ensite {$website->domain_name}.conf 2>&1";
            $enableOutput = shell_exec($enableSiteCommand);
            Log::info("a2ensite output: " . $enableOutput);

            // Check if the site is linked properly
            $sitesEnabled = shell_exec("ls -l /etc/apache2/sites-enabled/");
            Log::info("Current sites-enabled: " . $sitesEnabled);

            // Test Apache configuration before restarting
            $apacheTest = shell_exec("apachectl configtest 2>&1");
            Log::info("Apache config test: " . $apacheTest);

            if (strpos($apacheTest, 'Syntax OK') !== false) {
                shell_exec("systemctl restart apache2");
                Log::info("Apache restarted successfully after enabling site.");
            } else {
                Log::error("Apache configuration error detected! Fix manually.");
                return redirect()->back()->with('error', 'Apache configuration error! Check logs.');
            }

            // Generate SSL certificates
            $certbotCommand = "certbot --apache -d {$website->domain_name} -d www.{$website->domain_name} --non-interactive --expand --agree-tos -m admin@{$website->domain_name} 2>&1";
            $certbotOutput = shell_exec($certbotCommand);
            Log::info("Certbot output: " . $certbotOutput);

            // Reload Apache after SSL setup
            shell_exec("systemctl reload apache2");
            Log::info("Apache reloaded after SSL configuration");

            return redirect()->route('dashboard')->with('success', 'Website created successfully with SSL.');
        }
    } catch (\Exception $e) {
        Log::error("Error in storeWebsite function: " . $e->getMessage());
        return redirect()->back()->with('error', 'An error occurred: ' . $e->getMessage());
    }
    }

    
    public function deleteWebsite($domain)
    {
        try {
            Log::info("Attempting to delete SSL certificate for: " . $domain);

            // Check if SSL certificate exists before deleting
            $checkCertCommand = "certbot certificates | grep -w '{$domain}'";
            $certExists = shell_exec($checkCertCommand);

            if ($certExists) {
                Log::info("Certificate exists for {$domain}, proceeding with deletion.");

                // Run certbot delete command
                $deleteCommand = "echo 'Y' | sudo certbot delete --cert-name {$domain} 2>&1";
                $output = shell_exec($deleteCommand);
                Log::info("Certbot delete output: " . $output);
            } else {
                Log::warning("No certificate found for {$domain}");
            }

            // Disable Apache site
            $disableSiteCommand = "sudo a2dissite {$domain}-le-ssl.conf && sudo systemctl reload apache2";
            Log::info("Executing: " . $disableSiteCommand);
            shell_exec($disableSiteCommand);

            // Remove Apache configuration file
            $confFilePath = "/etc/apache2/sites-available/{$domain}.conf";
            if (file_exists($confFilePath)) {
                unlink($confFilePath);
                Log::info("Deleted Apache config file: " . $confFilePath);
            }

            // Restart Apache
            shell_exec("sudo systemctl reload apache2");
            Log::info("Apache reloaded after certificate deletion");

            return true;
        } catch (\Exception $e) {
            Log::error("Error deleting SSL certificate: " . $e->getMessage());
            return $e->getMessage();
        }
    }
}
