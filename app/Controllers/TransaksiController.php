<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Services\RajaOngkirService;

use App\Models\TransactionModel;
use App\Models\TransactionDetailModel;

class TransaksiController extends BaseController
{
    protected $cart;
    protected $transactionModel;
    protected $transactionDetailModel;

    public function __construct()
        {
            helper(['number', 'form']);
            $this->cart = service('cart');

            $this->transactionModel = new TransactionModel();
            $this->transactionDetailModel = new TransactionDetailModel(); 
        }
    
    public function index()
        {  
            $data = [
                'items' => $this->cart->contents(),
                'total' => $this->cart->total() 
            ];

            return view('v_keranjang', $data);
        }

    public function cart_add()
        {
            $this->cart->insert([
                'id'      => $this->request->getPost('id'),
                'qty'     => 1,
                'price'   => $this->request->getPost('harga'),
                'name'    => $this->request->getPost('nama'),
                'options' => [
                    'foto' => $this->request->getPost('foto')
                ]
            ]);
            
            session()->setFlashdata(
                'success',
                'Produk berhasil ditambahkan ke keranjang. 
                <a href="' . base_url('keranjang') . '">Lihat</a>'
            );
            
            return redirect()->to(base_url('/'));
        } 

    public function cart_edit()
        {
            $i = 1;
            foreach ($this->cart->contents() as $item) {
                $qty = $this->request->getPost('qty' . $i++);

                $this->cart->update([
                    'rowid' => $item['rowid'],
                    'qty'   => $qty
                ]);
            }

            session()->setFlashdata(
                'success',
                'Keranjang berhasil diperbarui'
            );

            return redirect()->to(base_url('keranjang'));
        }

    public function cart_delete($rowid)
        {
            $this->cart->remove($rowid);

            session()->setFlashdata(
                'success',
                'Produk berhasil dihapus dari keranjang'
            );

            return redirect()->to(base_url('keranjang'));
        }

    public function cart_clear()
        {
            $this->cart->destroy();

            session()->setFlashdata(
                'success',
                'Keranjang berhasil dikosongkan'
            );

            return redirect()->to(base_url('keranjang'));
        }

    public function checkout()
    {  
        $data = [
            'items'          => $this->cart->contents(),
            'total'          => $this->cart->total(),
            'defaultCourier' => 'jne',
            'weight'         => 1000
        ];

        return view('v_checkout', $data);
    }

    public function search_destination()
    {
        $keyword = $this->request->getGet('keyword');

        if (empty($keyword)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Keyword pencarian tidak boleh kosong.',
                'data'    => []
            ]);
        }

        try {
            $service = new RajaOngkirService();
            $response = $service->getDestination($keyword);

            return $this->response->setJSON([
                'success' => ($response['meta']['status'] ?? '') === 'success',
                'message' => $response['meta']['message'] ?? '',
                'data'    => $response['data'] ?? []
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => []
            ]);
        }
    }

    public function shipping_cost()
    {
        $origin = '64999'; // Semarang Barat default
        $destination = $this->request->getPost('destination');
        $weight = $this->request->getPost('weight') ?: '1000';
        $courier = $this->request->getPost('courier') ?: 'jne'; 

        if (empty($destination)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Tujuan pengiriman belum dipilih.',
                'data'    => [],
                'csrfHash' => csrf_hash()
            ]);
        }

        try {
            $service = new RajaOngkirService();
            $response = $service->getCost($origin, $destination, $weight, $courier);

            return $this->response->setJSON([
                'success' => ($response['meta']['status'] ?? '') === 'success',
                'message' => $response['meta']['message'] ?? '',
                'data'    => $response['data'] ?? [],
                'csrfHash' => csrf_hash()
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => [],
                'csrfHash' => csrf_hash()
            ]);
        }
    }

    public function checkout_process()
    { 
        $cartItems = $this->cart->contents();

        if (empty($cartItems)) {
            return redirect()->to(base_url('keranjang'))->with('failed', 'Keranjang belanja kosong');
        }

        $db = \Config\Database::connect();
        $db->transStart(); 

        $subtotal = 0;
        foreach ($cartItems as $item) {
            $subtotal += $item['qty'] * $item['price'];
        }

        $ongkir = (int) $this->request->getPost('ongkir');
        $username = session()->get('username');

        $transaction = [
            'username'    => $username,
            'alamat'      => $this->request->getPost('alamat'),
            'ongkir'      => $ongkir,
            'total_harga' => $subtotal + $ongkir,
            'status'      => 0, 
        ];

        // insert transaction
        if (!$this->transactionModel->insert($transaction)) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('failed', 'Gagal membuat transaksi');
        }

        $transactionId = $this->transactionModel->getInsertID();

        // insert transaction detail
        foreach ($cartItems as $item) {
            $this->transactionDetailModel->insert([
                'transaction_id' => $transactionId,
                'product_id'     => $item['id'],
                'jumlah'         => $item['qty'],
                'diskon'         => 0,
                'subtotal_harga' => $item['qty'] * $item['price'] 
            ]);
        }

        $db->transComplete();

        if (!$db->transStatus()) {
            return redirect()->back()->withInput()->with('failed', 'Gagal memproses transaksi');
        }

        // hapus session keranjang belanja 
        $this->cart->destroy();
        
        session()->setFlashdata('success', 'Transaksi berhasil dibuat!');
        return redirect()->to(base_url());
    }

    public function history()
{
    $username = session()->get('username'); 
 
    $transactions = $this->transactionModel->where('username', $username)->findAll();
    $transactionIds = array_column($transactions, 'id');

    $products = $this->transactionDetailModel->getProductsByTransactionIds($transactionIds);

    $data = [
        'username'      => $username,
        'transactions'  => $transactions,
        'products'      => $products
    ]; 

    return view('v_history', $data);
}
}