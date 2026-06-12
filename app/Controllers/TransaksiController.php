<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\TransactionDetailModel;
use App\Models\TransactionModel;

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
	    <a href="' . site_url('keranjang') . '">Lihat</a>'
	);
	
	return redirect()->to(site_url('/'));
} 
    public function cart_delete($rowid)
{
    $this->cart->remove($rowid);

    session()->setFlashdata(
        'success',
        'Produk berhasil dihapus dari keranjang'
    );

    return redirect()->to(site_url('keranjang'));
}

    public function cart_clear()
{
    $this->cart->destroy();

    session()->setFlashdata(
        'success',
        'Keranjang berhasil dikosongkan'
    );

    return redirect()->to(site_url('keranjang'));
}

public function cart_edit()
{
    $items = $this->cart->contents();
    $i = 1;

    foreach ($items as $item) {
        $qty = (int) $this->request->getPost('qty' . $i++);

        if ($qty > 0) {
            $this->cart->update([
                'rowid' => $item['rowid'],
                'qty' => $qty
            ]);
        }
    }

    session()->setFlashdata('success', 'Keranjang berhasil diperbarui');

    return redirect()->to(site_url('keranjang'));
}

public function checkout()
{
    $items = $this->cart->contents();

    if (empty($items)) {
        return redirect()->to(site_url('keranjang'))->with('failed', 'Keranjang masih kosong');
    }

    return view('v_checkout', [
        'items' => $items,
        'total' => $this->cart->total(),
        'weight' => $this->getCartWeight($items),
        'defaultCourier' => env('RAJAONGKIR_COURIER', 'jne')
    ]);
}

public function search_destination()
{
    $keyword = trim((string) $this->request->getGet('keyword'));

    if (strlen($keyword) < 3) {
        return $this->response->setJSON([
            'success' => true,
            'data' => []
        ]);
    }

    return $this->response->setJSON($this->rajaOngkirRequest(
        'GET',
        'destination/domestic-destination',
        [
            'search' => $keyword,
            'limit' => 50
        ]
    ));
}

public function shipping_cost()
{
    $destination = $this->request->getPost('destination');
    $courier = $this->request->getPost('courier') ?: env('RAJAONGKIR_COURIER', 'jne');
    $weight = (int) ($this->request->getPost('weight') ?: $this->getCartWeight($this->cart->contents()));
    $origin = env('RAJAONGKIR_ORIGIN_ID');

    if (!$origin) {
        return $this->response->setStatusCode(422)->setJSON([
            'success' => false,
            'message' => 'RAJAONGKIR_ORIGIN_ID belum diatur di file .env',
            'csrfHash' => csrf_hash()
        ]);
    }

    if (!$destination) {
        return $this->response->setStatusCode(422)->setJSON([
            'success' => false,
            'message' => 'Tujuan pengiriman belum dipilih',
            'csrfHash' => csrf_hash()
        ]);
    }

    $result = $this->rajaOngkirRequest(
        'POST',
        'calculate/domestic-cost',
        [
            'origin' => $origin,
            'destination' => $destination,
            'weight' => max(1, $weight),
            'courier' => $courier,
            'price' => 'lowest'
        ]
    );
    $result['csrfHash'] = csrf_hash();

    return $this->response->setJSON($result);
}

public function checkout_process()
{
    $items = $this->cart->contents();

    if (empty($items)) {
        return redirect()->to(site_url('keranjang'))->with('failed', 'Keranjang masih kosong');
    }

    $alamat = trim((string) $this->request->getPost('alamat'));
    $lokasi = trim((string) $this->request->getPost('lokasi_label'));
    $layanan = trim((string) $this->request->getPost('layanan_label'));
    $ongkir = (float) $this->request->getPost('ongkir');

    if ($alamat === '' || $lokasi === '' || $layanan === '' || $ongkir <= 0) {
        return redirect()->back()->withInput()->with('failed', 'Lengkapi alamat, lokasi, dan layanan ongkir terlebih dahulu');
    }

    $db = db_connect();
    $db->transStart();

    $transactionId = $this->transactionModel->insert([
        'username' => session()->get('username'),
        'total_harga' => $this->cart->total() + $ongkir,
        'alamat' => $alamat . "\n" . $lokasi . "\n" . $layanan,
        'ongkir' => $ongkir,
        'status' => 0
    ]);

    foreach ($items as $item) {
        $this->transactionDetailModel->insert([
            'transaction_id' => $transactionId,
            'product_id' => $item['id'],
            'jumlah' => $item['qty'],
            'diskon' => 0,
            'subtotal_harga' => $item['subtotal']
        ]);
    }

    $db->transComplete();

    if (!$db->transStatus()) {
        return redirect()->back()->withInput()->with('failed', 'Pesanan gagal dibuat');
    }

    $this->cart->destroy();

    return redirect()->to(site_url('keranjang'))->with('success', 'Pesanan berhasil dibuat');
}

private function getCartWeight(array $items): int
{
    $qty = 0;

    foreach ($items as $item) {
        $qty += (int) $item['qty'];
    }

    return max(1000, $qty * 1000);
}

private function rajaOngkirRequest(string $method, string $endpoint, array $data): array
{
    $apiKey = env('RAJAONGKIR_API_KEY');
    $baseUrl = rtrim(env('RAJAONGKIR_BASE_URL', 'https://rajaongkir.komerce.id/api/v1'), '/');

    if (!$apiKey) {
        return [
            'success' => false,
            'message' => 'RAJAONGKIR_API_KEY belum diatur di file .env'
        ];
    }

    try {
        $client = service('curlrequest');
        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'key' => $apiKey
            ],
            'http_errors' => false
        ];

        if (strtoupper($method) === 'GET') {
            $options['query'] = $data;
        } else {
            $options['form_params'] = $data;
        }

        $response = $client->request($method, $baseUrl . '/' . ltrim($endpoint, '/'), $options);
        $body = json_decode($response->getBody(), true);

        if (!is_array($body)) {
            return [
                'success' => false,
                'message' => 'Response RajaOngkir tidak valid',
                'data' => []
            ];
        }

        if ($response->getStatusCode() >= 400 || ($body['meta']['status'] ?? '') === 'error') {
            return [
                'success' => false,
                'message' => $body['meta']['message'] ?? $body['message'] ?? 'Request RajaOngkir gagal',
                'data' => []
            ];
        }

        return [
            'success' => true,
            'data' => $body['data'] ?? [],
            'raw' => $body
        ];
    } catch (\Throwable $e) {
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'data' => []
        ];
    }
}
}
