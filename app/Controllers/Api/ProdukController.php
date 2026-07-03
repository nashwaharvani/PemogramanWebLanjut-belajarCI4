<?php

namespace App\Controllers\Api;

use App\Models\ProductModel;
use CodeIgniter\RESTful\ResourceController;

class ProdukController extends ResourceController
{
    protected $modelName = ProductModel::class;
    protected $format    = 'json';
    private $token;

    public function __construct()
    {
        $this->token = env('MY_API_KEY');
    }

    private function authenticate()
    {
        $header = $this->request->getHeaderLine('Authorization');

        if (empty($header)) {
            return false;
        }

        if (!preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return false;
        }

        return $matches[1] === $this->token;
    }

    private function unauthorized()
    {
        return $this->failUnauthorized('Unauthorized');
    }

    public function index()
    {
        if (! $this->authenticate()) {
            return $this->unauthorized();
        }

        $products = $this->model->findAll();

        return $this->respond($products);
    }

    public function show($id = null)
    {
        if (! $this->authenticate()) {
            return $this->unauthorized();
        }

        if (empty($id)) {
            return $this->failValidationErrors('ID produk diperlukan');
        }

        $product = $this->model->find($id);

        if (! $product) {
            return $this->failNotFound('Produk tidak ditemukan');
        }

        return $this->respond($product);
    }

    public function new()
    {
        return $this->failNotFound('Endpoint tidak tersedia');
    }

    public function create()
    {
        if (! $this->authenticate()) {
            return $this->unauthorized();
        }

        $data = $this->request->getJSON(true);

        if (empty($data)) {
            return $this->failValidationErrors('Data JSON tidak valid');
        }

        $insertId = $this->model->insert($data);

        if ($insertId === false) {
            return $this->fail($this->model->errors());
        }

        return $this->respondCreated([
            'message' => 'Produk berhasil ditambahkan',
            'id'      => $insertId,
        ]);
    }

    public function edit($id = null)
    {
        return $this->failNotFound('Endpoint tidak tersedia');
    }

    public function update($id = null)
    {
        if (! $this->authenticate()) {
            return $this->unauthorized();
        }

        if (empty($id)) {
            return $this->failValidationErrors('ID produk diperlukan');
        }

        $product = $this->model->find($id);

        if (! $product) {
            return $this->failNotFound('Produk tidak ditemukan');
        }

        $data = $this->request->getJSON(true);

        if (empty($data)) {
            return $this->failValidationErrors('Data JSON tidak valid');
        }

        if ($this->model->update($id, $data) === false) {
            return $this->fail($this->model->errors());
        }

        return $this->respond([
            'message' => 'Produk berhasil diperbarui',
        ]);
    }

    public function delete($id = null)
    {
        if (! $this->authenticate()) {
            return $this->unauthorized();
        }

        if (empty($id)) {
            return $this->failValidationErrors('ID produk diperlukan');
        }

        if (! $this->model->find($id)) {
            return $this->failNotFound('Produk tidak ditemukan');
        }

        $this->model->delete($id);

        return $this->respondDeleted([
            'message' => 'Produk berhasil dihapus',
        ]);
    }
}
