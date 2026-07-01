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
        $dataFoto = $this->request->getFile('foto');

        $dataForm = [
            'nama' => $this->request->getPost('nama'),
            'harga' => $this->request->getPost('harga'),
            'jumlah' => $this->request->getPost('jumlah') 
        ];

        if ($dataFoto->isValid()) {
            $fileName = $dataFoto->getRandomName(); 
            $dataFoto->move('img/', $fileName);
            
            $dataForm['foto'] = $fileName;
        }

        $this->productModel->insert($dataForm);

        return redirect()->to(site_url('produk'))->with('success', 'Data Berhasil Ditambah');
    } 

    public function edit($id)
    {
        $produk = $this->productModel->find($id);

        if (!$produk) {
            return redirect()->to(site_url('produk'))->with('failed', 'Data produk tidak ditemukan');
        }

        $dataForm = [
            'nama' => $this->request->getPost('nama'),
            'harga' => $this->request->getPost('harga'),
            'jumlah' => $this->request->getPost('jumlah')
        ];

        if ($this->request->getPost('check')) {
            $dataFoto = $this->request->getFile('foto');

            if ($dataFoto->isValid()) {
                $fileName = $dataFoto->getRandomName();
                $dataFoto->move('img/', $fileName);

                $dataForm['foto'] = $fileName;
            }
        }

        $this->productModel->update($id, $dataForm);

        return redirect()->to(site_url('produk'))->with('success', 'Data Berhasil Diubah');
    }

    public function delete($id)
    {
        $produk = $this->productModel->find($id);

        if (!$produk) {
            return redirect()->to(site_url('produk'))->with('failed', 'Data produk tidak ditemukan');
        }

        $this->productModel->delete($id);

        return redirect()->to(site_url('produk'))->with('success', 'Data Berhasil Dihapus');
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
