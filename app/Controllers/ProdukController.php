<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use Dompdf\Dompdf;

class ProdukController extends BaseController
{
    protected $productModel;

    public function __construct()
    {
        $this->productModel = new ProductModel();
    }

    public function index()
    {
        $products = $this->productModel->findAll();

        return view('produk/index', [
            'products' => $products,
        ]);
    }

    public function create()
    {
        if ($this->request->getMethod() !== 'post') {
            return redirect()->to(site_url('produk'));
        }

        $data = $this->request->getPost(['nama', 'harga', 'jumlah']);
        $file = $this->request->getFile('foto');

        if ($file && $file->isValid() && ! $file->hasMoved()) {
            $newName = $file->getRandomName();
            $file->move(FCPATH . 'img', $newName);
            $data['foto'] = $newName;
        }

        $this->productModel->insert($data);

        session()->setFlashdata('success', 'Produk berhasil ditambahkan');
        return redirect()->to(site_url('produk'));
    }

    public function edit($id = null)
    {
        if ($this->request->getMethod() !== 'post') {
            return redirect()->to(site_url('produk'));
        }

        $product = $this->productModel->find($id);

        if (! $product) {
            session()->setFlashdata('failed', 'Produk tidak ditemukan');
            return redirect()->to(site_url('produk'));
        }

        $data = $this->request->getPost(['nama', 'harga', 'jumlah']);
        $photoCheck = $this->request->getPost('check');
        $file = $this->request->getFile('foto');

        if ($photoCheck && $file && $file->isValid() && ! $file->hasMoved()) {
            if (! empty($product['foto']) && file_exists(FCPATH . 'img/' . $product['foto'])) {
                @unlink(FCPATH . 'img/' . $product['foto']);
            }

            $newName = $file->getRandomName();
            $file->move(FCPATH . 'img', $newName);
            $data['foto'] = $newName;
        } else {
            $data['foto'] = $product['foto'];
        }

        $this->productModel->update($id, $data);

        session()->setFlashdata('success', 'Produk berhasil diubah');
        return redirect()->to(site_url('produk'));
    }

    public function delete($id = null)
    {
        $product = $this->productModel->find($id);

        if (! $product) {
            session()->setFlashdata('failed', 'Produk tidak ditemukan');
            return redirect()->to(site_url('produk'));
        }

        if (! empty($product['foto']) && file_exists(FCPATH . 'img/' . $product['foto'])) {
            @unlink(FCPATH . 'img/' . $product['foto']);
        }

        $this->productModel->delete($id);

        session()->setFlashdata('success', 'Produk berhasil dihapus');
        return redirect()->to(site_url('produk'));
    }

    public function download()
    {
        $products = $this->productModel->findAll();

        $html = view('produk/download_pdf', [
            'products' => $products,
        ]);

        $filename = date('Y-m-d-H-i-s') . '-produk.pdf';

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream($filename, [
            'Attachment' => true,
        ]);
    }
}
