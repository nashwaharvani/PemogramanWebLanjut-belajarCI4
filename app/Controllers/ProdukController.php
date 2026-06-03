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
        $this->productModel = new ProductModel();
    }
    public function index()
    {
       return view('produk/index', [
    'products' => $this->productModel->findAll()
    ]);
    }
}
