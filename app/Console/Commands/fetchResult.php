<?php

namespace App\Console\Commands;

use App\Models\Results;
use DOMDocument;
use DOMXPath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class fetchResult extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Initialize a cURL session
        $ch = curl_init();

        // Set the URL you want to fetch data from
        $url = "https://psc.gov.np/category/sangathit-results.html";  // Replace with the target URL

        // Set the cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  // Return the response as a string

        // Execute the cURL session and fetch the data
        $response = curl_exec($ch);

        // Check for errors
        if ($response === false) {
            echo "cURL Error: " . curl_error($ch);
            curl_close($ch);
            exit;
        }

        // Close the cURL session
        curl_close($ch);

        // Load the HTML response into a DOMDocument
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);  // Suppress HTML5 warnings
        $dom->loadHTML($response);
        libxml_clear_errors();

        // Create a new DOMXPath object
        $xpath = new DOMXPath($dom);

        // Query for all links within the table with id 'table1'
        $query = '//table[@id="datatable1"]//a[@href]';
        $links = $xpath->query($query);

        // Array to store PDF links
        $pdfLinks = [];

        // Loop through the links and filter out only PDF links
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            $linkText = $link->textContent;
            if (preg_match('/\.pdf$/', $href)) {
                $pdfLinks[] = [
                    'href' => $href,
                    'text' => $linkText
                ];
            }
        }


        if (Results::where(['result' => $pdfLinks[0]['href']])->exists()) {
            $this->info('Not updated.');
        } else {
            $toEmailArray = ['amt.tmg@gmail.com']; // Replace with your email address

            foreach ($toEmailArray as $toEmail) {
                Mail::raw($pdfLinks[0]['href'] . ': ' . trim($pdfLinks[0]['text']), function ($message) use ($toEmail) {
                    $message->to($toEmail)
                        ->subject('Result Update Notification')
                        ->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                });
            }
            $result = Results::create(
                ['result' => $pdfLinks[0]['href']]
            );
            $this->info($pdfLinks[0]['text']);
        }
    }
}
