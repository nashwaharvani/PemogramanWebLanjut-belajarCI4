<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;

use App\Models\ProductModel;
use Dompdf\Dompdf;

class ProdukController extends BaseController
{
    use ResponseTrait;

    protected $productModel;
    private $token;

    public function __construct()
    {
        $this->productModel = new ProductModel();
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
    return $this->respond([
        'status'  => false,
        'message' => 'Unauthorized'
    ], 401);
}
    public function index()
    {
        // For web UI, return the produk view with product list.
        $products = $this->productModel->findAll();

        $data = [
            'products' => $products
        ];

        return view('produk/index', $data);
    }

    public function create()
{
    if (!$this->authenticate()) {
        return $this->unauthorized();
    }

    $data = $this->request->getJSON(true);

    $this->model->insert($data);

    return $this->respondCreated([
        'message' => 'Produk berhasil ditambahkan'
    ]);
}
    public function show($id = null)
{
    if (!$this->authenticate()) {
        return $this->unauthorized();
    }

    $product = $this->model->find($id);

    if (!$product) {
        return $this->failNotFound('Produk tidak ditemukan');
    }

    return $this->respond($product);
} 

    public function update($id = null)
{
    if (!$this->authenticate()) {
        return $this->unauthorized();
    }

    if (!$this->model->find($id)) {
        return $this->failNotFound('Produk tidak ditemukan');
    }

    $data = $this->request->getJSON(true);

    $this->model->update($id, $data);

    return $this->respond([
        'message' => 'Produk berhasil diperbarui'
    ]);
}

    public function delete($id = null)
{
    if (!$this->authenticate()) {
        return $this->unauthorized();
    }

    if (!$this->model->find($id)) {
        return $this->failNotFound('Produk tidak ditemukan');
    }

    $this->model->delete($id);

    return $this->respondDeleted([
        'message' => 'Produk berhasil dihapus'
    ]);
}
        public function download()
    {
        // Ambil data produk dari database
        $products = $this->productModel->findAll();

        // Render view menjadi HTML
        $html = view('produk/download_pdf', [
            'products' => $products
        ]);

        // Nama file PDF
        $filename = date('Y-m-d-H-i-s') . '-produk.pdf';

        // Inisialisasi Dompdf
        $dompdf = new Dompdf();

        // Load HTML ke Dompdf
        $dompdf->loadHtml($html);

        // Setting ukuran kertas dan orientasi
        $dompdf->setPaper('A4', 'portrait');

        // Generate PDF
        $dompdf->render();

        // Download / tampilkan PDF
        $dompdf->stream($filename, [
            'Attachment' => true
        ]);
    }
}
