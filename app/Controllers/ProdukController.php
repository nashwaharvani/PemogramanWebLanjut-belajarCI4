<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

use App\Models\ProductModel;

class ProdukController extends BaseController
{
    protected $productModel; 

    function __construct()
    {
        helper('form');
        $this->productModel = new ProductModel();
    }
    public function index()
    {
        return view('produk/index', [
            'products' => $this->productModel->findAll()
        ]);
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
}
