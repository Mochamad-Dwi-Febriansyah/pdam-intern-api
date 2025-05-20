<?php

namespace App\Http\Controllers;

use App\ApiResponse;
use App\Helpers\LogHelper;
use App\Http\Requests\SignatureRequest;
use App\Models\Signature;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SignatureController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        // $signatures = Signature::paginate(20);
        $signatures = Signature::query()
            ->orderByRaw(
                "JSON_CONTAINS(JSON_EXTRACT(purposes, '$[*].status'), '\"active\"') DESC"
            )->paginate(20);

        LogHelper::log('signatures_index', 'Retrieved the list of signatures successfully', null, ['total_signatures' => $signatures->total()]);

        return $this->successResponse($signatures, 'Signatures list retrieved successfully', Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SignatureRequest $signaturerequest)
    {
        DB::beginTransaction();

        try {
            $signature = Signature::create(
                $signaturerequest->validated()
            );
            $activePurposes = collect($signature->purposes)
                ->where('status', 'active')
                ->pluck('name')
                ->toArray();

            if (!empty($activePurposes)) {
                $otherSignatures = Signature::where('id', '!=', $signature->id)->get();

                foreach ($otherSignatures as $otherSignature) {
                    $purposes = $otherSignature->purposes;
                    $updated = false;

                    foreach ($purposes as &$p) {
                        if (in_array($p['name'], $activePurposes) && ($p['status'] ?? null) === 'active') {
                            $p['status'] = 'inactive';
                            $updated = true;
                        }
                    }

                    // Kalau memang ada perubahan, baru update
                    if ($updated) {
                        $otherSignature->update([
                            'purposes' => $purposes
                        ]);
                    }
                }
            }
            // dd($otherSignature);

            DB::commit();

            LogHelper::log('signature_store', 'Created a new signature successfully', $signature, [
                'signature' => $signature->id
            ]);

            $data = [
                'data' => $signature
            ];

            return $this->successResponse($data, 'Signature has been successfully created', Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('signature_store', 'Failed to create a new signature', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while creating the signature', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $signature = Signature::find($id);

        if (!$signature) {
            return $this->errorResponse(null, 'Signature not found', Response::HTTP_NOT_FOUND);
        }

        LogHelper::log('signatures_show', 'Viewed signature details successfully', $signature);

        return $this->successResponse($signature, 'Signature details retrieved successfully', Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SignatureRequest $signaturerequest, string $id)
    {
        DB::beginTransaction();

        try {
            $signature = Signature::find($id);

            if (!$signature) {
                return $this->errorResponse(null, 'Signature not found', Response::HTTP_NOT_FOUND);
            }

            $signatureData = array_filter($signaturerequest->validated(), fn($value) => $value !== null);

            if (!empty($signatureData)) {
                $signature->fill($signatureData)->save();
            }

            $activePurposes = collect($signature->purposes)
                ->where('status', 'active')
                ->pluck('name')
                ->toArray();

            if (!empty($activePurposes)) {
                $otherSignatures = Signature::where('id', '!=', $signature->id)->get();

                foreach ($otherSignatures as $otherSignature) {
                    $purposes = $otherSignature->purposes;
                    $updated = false;

                    foreach ($purposes as &$p) {
                        if (in_array($p['name'], $activePurposes) && ($p['status'] ?? null) === 'active') {
                            $p['status'] = 'inactive';
                            $updated = true;
                        }
                    }

                    // Kalau memang ada perubahan, baru update
                    if ($updated) {
                        $otherSignature->update([
                            'purposes' => $purposes
                        ]);
                    }
                }
            }

            DB::commit();

            LogHelper::log('signature_update', 'Signature updated successfully', $signature, [
                'updated_fields' => $signatureData,
            ]);

            return $this->successResponse(null, 'Signature has been successfully updated', Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('signature_update', 'Failed to update signature', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while updating the signature', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            $signature = Signature::find($id);

            if (!$signature) {
                return $this->errorResponse(null, 'Signature not found', Response::HTTP_NOT_FOUND);
            }

            $signature->delete();

            DB::commit();

            LogHelper::log('signature_destroy', 'Signature deleted successfully', $signature, [
                'deleted_signature_id' => $signature->id,
                'deleted_signature_name' => $signature->name
            ]);

            return $this->successResponse(null, 'Signature has been successfully deleted', Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('signature_destroy', 'Failed to delete signature', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while deleting the signature', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function syncFromSSO(Request $request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return $this->errorResponse(null, 'Token not found', Response::HTTP_UNAUTHORIZED);
        }

        // Ambil data signature dari SSO
        $ssoUsers = $this->getAllUserDataFromSSO($token); // Misalnya, ini mengambil data dari API atau service SSO
        // dd($ssoUsers);
        if (empty($ssoUsers) || empty($ssoUsers['data']) || !is_array($ssoUsers['data'])) {
            return $this->errorResponse(null, 'Tidak ada data user dari SSO.', Response::HTTP_NOT_FOUND);
        }
        DB::beginTransaction();

        try {
            // Proses untuk setiap user yang ada di data SSO
            foreach ($ssoUsers['data'] as $ssoUser) {
                // Cek apakah signature sudah ada di lokal berdasarkan user_id
                $localSignature = Signature::where('user_id', $ssoUser['npp'])->first();

                if ($localSignature) {
                    // Update data signature lokal hanya untuk kolom yang diperlukan
                    $localSignature->update([
                        'name_snapshot' => $ssoUser['nama'],
                        'rank_group' => $ssoUser['pangkat_golongan'],
                        'position' => $ssoUser['jabatan'],
                        'nik' => $ssoUser['nik'],
                    ]);
                }
            }

            // Commit transaksi jika semua berjalan lancar
            DB::commit();

            return $this->successResponse(null, 'Signatures synced successfully', Response::HTTP_OK);
        } catch (\Throwable $th) {
            // Rollback jika ada error
            DB::rollBack();

            LogHelper::log('signature_sync', 'Failed to sync signatures', null, [], 'error');

            return $this->errorResponse($th->getMessage(), 'An error occurred while syncing signatures', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function getAllUserDataFromSSO($token)
    {
        // Misalnya menggunakan HTTP client seperti Guzzle untuk mengambil data dari API SSO
        try {
            // Gantilah dengan URL endpoint API yang sesuai dengan server SSO Anda
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token
            ])->get(config('services.sso.server_url') . '/api/client/user/all-pegawai');

            // Periksa jika response sukses
            if ($response->successful()) {
                // Ambil data pengguna dari response JSON
                $ssoUsers = $response->json();

                // Kembalikan data pengguna yang diterima
                return $ssoUsers;
            } else {
                // Tangani error jika response gagal
                throw new \Exception('Failed to fetch data from SSO API');
            }
        } catch (\Exception $e) {
            // Tangani error jika terjadi masalah dalam melakukan request
            Log::error('Error fetching user data from SSO: ' . $e->getMessage());
            return []; // Mengembalikan array kosong jika terjadi kesalahan
        }
    }
}
