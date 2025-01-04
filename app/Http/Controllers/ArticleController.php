<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArticleIndexRequest;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Models\CachedFeed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class ArticleController extends Controller
{
    private $apiKey;
    private $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('auth.guardian_api_key');
        $this->baseUrl = config('app.guardian_api_url');
    }

    /**
     * List All Article
     * @param ArticleIndexRequest $request
     * @return JsonResponse
     */
    public function index(ArticleIndexRequest $request, $sectionName): JsonResponse
    {
        // Validate section name
        $validator = Validator::make(
            ['section_name' => $sectionName],
            ['section_name' => 'required|regex:/^[a-z-]+$/']
        );

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid section name'], 400);
        }
        $contentType = $request->header('Content-Type');
        
        if ($contentType !== '' && $contentType !== null && !empty($contentType)) {
            if (strpos($contentType, 'application/json') !== false) {
                $data = $request->json()->all();
            } elseif (strpos($contentType, 'application/xml') !== false) {
                $data = simplexml_load_string($request->getContent());
                // Convert XML object to an array (if needed)
                $data = json_decode(json_encode($data), true);
            } else {
                return response()->json(['error' => 'Unsupported Content-Type'], 415);
            }
        }

        // Check if cached data exists and is valid (10-minute cache)
        $cachedFeed = CachedFeed::where('section_name', $sectionName)
            ->where('updated_at', '>=', Carbon::now()->subMinutes(10))
            ->first();

        if ($cachedFeed) {
            return response()->json($cachedFeed->data, 200)
                ->header('Content-Type', 'application/rss+xml');
        }
        
        try {
            $client = new Client();
            $response = $client->get("{$this->baseUrl}/sections?q={$sectionName}&api-key={$this->apiKey}");
            $data = json_decode($response->getBody()->getContents(), true)['response']['results'];
            
            if ($response->getStatusCode() == 200) {
                
                $rss = "";
                foreach ($data as $key => $d) {
                    $rss .= $this->generateRssFeed($d);   
                }
                
                // Cache the data
                CachedFeed::updateOrCreate(
                    ['section_name' => $sectionName],
                    ['data' => $rss, 'updated_at' => now()]
                );

                return response()->json($rss, 200)
                    ->header('Content-Type', 'application/rss+xml');
            }else{
                return response()->json($response, $response->getStatusCode());
            }
        } catch (\Exception $e) {
            Log::error('Error fetching data from The Guardian API', [
                'error' => $e->getMessage(),
                'section' => $sectionName
            ]);
            return response()->json(['error' => 'Failed to fetch data'], 500);
        }
    }

    private function generateRssFeed(array $data)
    {
        $rss = '<?xml version="1.0" encoding="UTF-8"?>';
        $rss .= '<rss version="2.0">';
        $rss .= '<channel>';
        $rss .= '<title>' . htmlspecialchars($data['webTitle']) . ' News</title>';
        $rss .= '<link>' . $data['webUrl'] . '</link>';
        $rss .= '<description>Latest news from The Guardian</description>';

        foreach ($data['editions'] as $item) {
            $rss .= '<item>';
            $rss .= '<title>' . htmlspecialchars($item['webTitle']) . '</title>';
            $rss .= '<link>' . $item['webUrl'] . '</link>';
            $rss .= '<description>Latest news from The Guardian : "'.$item['webTitle'].'"</description>';
            $rss .= '</item>';
        }

        $rss .= '</channel>';
        $rss .= '</rss>';

        return $rss;
    }
}
