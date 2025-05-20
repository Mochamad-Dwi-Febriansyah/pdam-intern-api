<?php

namespace App;
use Illuminate\Http\Response as HttpResponse;

trait ApiResponse
{
          /**
     * Format response sukses
     *
     * @param mixed $data
     * @param string $message
     * @param int $status
     * @return \Illuminate\Http\JsonResponse
     */
    public function successResponse($data = null, $message = 'Request successfull', $status = HttpResponse::HTTP_OK, $pagination = null)
    {
        $response = [
            'status' => $status,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        // Jika pagination diberikan, tambahkan ke dalam response
        if ($pagination) { 
            $response['data'] = $pagination;
        }

        return response()->json($response, $status);
    }

    /**
     * Format response error
     *
     * @param string $message
     * @param int $status
     * @param mixed $data
     * @return \Illuminate\Http\JsonResponse
     */
    public function errorResponse($errors = null, $message = 'Request failed', $status = HttpResponse::HTTP_BAD_REQUEST)
    {
        $response = [
        'status' => $status,
        'message' => $message,
        ];
 
        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Format pagination data
     *
     * @param \Illuminate\Pagination\LengthAwarePaginator $paginator
     * @return array
     */
    public function paginationData($paginator, $data = null)
    {
        return [
            'current_page' => $paginator->currentPage(),
            'data' => $data,
            'first_page_url' => $paginator->url(1),
            'from' => $paginator->firstItem(),
            'last_page' => $paginator->lastPage(),
            'last_page_url' => $paginator->url($paginator->lastPage()),
            'next_page_url' => $paginator->nextPageUrl(),
            'prev_page_url' => $paginator->previousPageUrl(),
            'to' => $paginator->lastItem(),
            'total' => $paginator->total(),
            'links' => $this->getPaginationLinks($paginator),
        ];
    }

    /**
     * Generate pagination links for the response
     *
     * @param \Illuminate\Pagination\LengthAwarePaginator $paginator
     * @return array
     */
    private function getPaginationLinks($paginator)
    {
        $links = [];

        // Previous Link
        $links[] = [
            'url' => $paginator->previousPageUrl(),
            'label' => 'pagination.previous',
            'active' => $paginator->onFirstPage() ? false : true,
        ];

        // Page Links
        foreach (range(1, $paginator->lastPage()) as $page) {
            $links[] = [
                'url' => $paginator->url($page),
                'label' => (string)$page,
                'active' => $paginator->currentPage() == $page,
            ];
        }

        // Next Link
        $links[] = [
            'url' => $paginator->nextPageUrl(),
            'label' => 'pagination.next',
            'active' => $paginator->hasMorePages(),
        ];

        return $links;
    }
}
