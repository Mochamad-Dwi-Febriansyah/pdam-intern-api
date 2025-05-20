<?php

namespace App\Http\Controllers;

use App\ApiResponse;
use App\Models\Document;
use App\Models\SchoolUni;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StatisticController extends Controller
{
    use ApiResponse;

    protected $models = [
        'users' => [
            'class' => User::class,
            'title' => 'Total Pengguna',
            'permission' => 'pdamintern.applications.view'
        ],
        'documents' => [
            'class' => Document::class,
            'title' => 'Total Pengajuan Berkas',
            'permission' => 'pdamintern.applications.view'
        ],
        'school_university' => [
            'class' => SchoolUni::class,
            'title' => 'Total Sekolah/Universitas',
            'permission' => 'pdamintern.applications.view'
        ],
    ];

    public function getStatistics(Request $request)
    {
        // Ambil parameter `stats` dari query string, defaultnya ambil semua key model
        $requestedStats = $request->query('stats', array_keys($this->models));

        $cards = [];

        foreach ($requestedStats as $stat) {
            if (isset($this->models[$stat])) {
                $modelInfo = $this->models[$stat];
                $cards[] = [
                    'title' => $modelInfo['title'],
                    'value' => $modelInfo['class']::count(),
                    'permission' => $modelInfo['permission'],
                ];
            }
        }

        return $this->successResponse($cards, 'Statistics cards retrieved successfully', Response::HTTP_OK);
    }
}
