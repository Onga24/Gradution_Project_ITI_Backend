<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ExecutionController extends Controller
{
    public function execute(Request $request)
    {
        $data = $request->validate([
            'language' => 'required|string',
            'code'     => 'required|string',
            'version'  => 'nullable|string',
            'stdin'    => 'nullable|string|max:2000',
            'files'    => 'nullable|array',
            'files.*.name'    => 'required_with:files|string',
            'files.*.content' => 'required_with:files|string',
        ]);

        // Safety limits
        if (strlen($data['code']) > 20000) {
            return response()->json(['success' => false, 'message' => 'Code too long (max 20k chars)'], 413);
        }

        $pistonUrl = env('PISTON_URL', 'https://emkc.org/api/v2/piston');

        // resolve version if not provided by calling /runtimes
        $version = $data['version'] ?? null;
        if (!$version) {
            try {
                $runtimesResp = Http::timeout(10)->get($pistonUrl . '/runtimes')->throw();
                $runtimes = $runtimesResp->json();
                $langLow = strtolower($data['language']);
                $match = null;
                foreach ($runtimes as $rt) {
                    if (strtolower($rt['language']) === $langLow || in_array($langLow, array_map('strtolower', $rt['aliases'] ?? []))) {
                        $match = $rt;
                        break;
                    }
                }
                if (!$match) {
                    return response()->json(['success' => false, 'message' => 'Language not supported by remote executor'], 422);
                }
                $version = $match['version'];
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Failed to fetch runtimes from piston: ' . $e->getMessage()], 500);
            }
        }

        $files = $data['files'] ?? [
            ['name' => 'Main.' . $this->extFromLang($data['language']), 'content' => $data['code']]
        ];

        $payload = [
            'language' => $data['language'],
            'version'  => $version,
            'files'    => $files,
            'stdin'    => $data['stdin'] ?? '',
        ];

        try {
            $resp = Http::timeout(30)->post($pistonUrl . '/execute', $payload);
            if ($resp->failed()) {
                return response()->json(['success' => false, 'message' => 'Remote execution failed', 'details' => $resp->body()], 500);
            }
            return response()->json(['success' => true, 'result' => $resp->json()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Execution request error: ' . $e->getMessage()], 500);
        }
    }

    private function extFromLang($language)
    {
        $map = [
            'python' => 'py',
            'javascript' => 'js',
            'typescript' => 'ts',
            'php' => 'php',
            'c' => 'c',
            'cpp' => 'cpp',
            'java' => 'java',
            'go' => 'go',
            'ruby' => 'rb',
            'bash' => 'sh',
        ];
        return $map[strtolower($language)] ?? 'txt';
    }
}

