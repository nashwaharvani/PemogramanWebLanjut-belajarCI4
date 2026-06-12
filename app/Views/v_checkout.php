<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<?php if (session()->getFlashData('failed')) : ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= session()->getFlashData('failed') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-7">
        <?= form_open(site_url('checkout'), ['id' => 'checkoutForm']) ?>
        <input type="hidden" id="csrfField" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

        <div class="mb-3">
            <label for="nama" class="form-label">Nama</label>
            <input type="text" class="form-control" id="nama" value="<?= esc(session()->get('username')) ?>" readonly>
        </div>

        <div class="mb-3">
            <label for="alamat" class="form-label">Alamat</label>
            <input type="text" class="form-control" id="alamat" name="alamat" value="<?= old('alamat') ?>" required>
        </div>

        <div class="mb-3">
            <label for="lokasiKeyword" class="form-label">Kelurahan</label>
            <input type="text" class="form-control" id="lokasiKeyword" placeholder="Ketik minimal 3 huruf lokasi tujuan" autocomplete="off" required>
            <select class="form-select mt-2" id="lokasiSelect" size="5" hidden></select>
            <input type="hidden" id="destination" name="destination" value="<?= old('destination') ?>">
            <input type="hidden" id="lokasiLabel" name="lokasi_label" value="<?= old('lokasi_label') ?>">
            <div class="form-text" id="lokasiHelp">Contoh: banjardowo, semarang, jawa tengah.</div>
        </div>

        <div class="mb-3">
            <label for="layanan" class="form-label">Layanan</label>
            <select class="form-select" id="layanan" name="layanan" disabled required>
                <option value="">Pilih lokasi pengiriman terlebih dahulu</option>
            </select>
            <input type="hidden" id="layananLabel" name="layanan_label" value="<?= old('layanan_label') ?>">
        </div>

        <div class="mb-3">
            <label for="ongkir" class="form-label">Ongkir</label>
            <input type="number" class="form-control" id="ongkir" name="ongkir" value="<?= old('ongkir') ?>" readonly required>
        </div>

        <input type="hidden" id="courier" value="<?= esc($defaultCourier) ?>">
        <input type="hidden" id="weight" value="<?= esc($weight) ?>">

        <button type="submit" class="btn btn-primary">Buat Pesanan</button>
        <?= form_close() ?>
    </div>

    <div class="col-lg-5">
        <table class="table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Harga</th>
                    <th>Jumlah</th>
                    <th>Sub Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item) : ?>
                    <tr>
                        <td><?= esc($item['name']) ?></td>
                        <td><?= number_to_currency($item['price'], 'IDR') ?></td>
                        <td><?= esc($item['qty']) ?></td>
                        <td><?= number_to_currency($item['subtotal'], 'IDR') ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="3" class="text-end">Subtotal</td>
                    <td><?= number_to_currency($total, 'IDR') ?></td>
                </tr>
                <tr>
                    <td colspan="3" class="text-end">Total</td>
                    <td id="grandTotal"><?= number_to_currency($total, 'IDR') ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    const destinationUrl = "<?= site_url('checkout/destination') ?>";
    const costUrl = "<?= site_url('checkout/cost') ?>";
    const csrfName = "<?= csrf_token() ?>";
    let csrfHash = "<?= csrf_hash() ?>";
    const cartTotal = <?= (float) $total ?>;

    const lokasiKeyword = document.getElementById('lokasiKeyword');
    const lokasiSelect = document.getElementById('lokasiSelect');
    const lokasiHelp = document.getElementById('lokasiHelp');
    const destination = document.getElementById('destination');
    const lokasiLabel = document.getElementById('lokasiLabel');
    const layanan = document.getElementById('layanan');
    const layananLabel = document.getElementById('layananLabel');
    const ongkir = document.getElementById('ongkir');
    const courier = document.getElementById('courier');
    const weight = document.getElementById('weight');
    const grandTotal = document.getElementById('grandTotal');

    let searchTimer;

    function formatCurrency(value) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0
        }).format(value);
    }

    function destinationLabel(item) {
        if (item.label) {
            return item.label;
        }

        const parts = [
            item.subdistrict_name || item.district_name || item.name,
            item.city_name || item.regency_name,
            item.province_name,
            item.zip_code
        ].filter(Boolean);

        return parts.join(', ');
    }

    function destinationId(item) {
        return item.id || item.destination_id || item.subdistrict_id || item.district_id || item.city_id;
    }

    function costValue(item) {
        if (item.cost) {
            return Array.isArray(item.cost) ? item.cost[0].value : item.cost;
        }

        return item.price || item.value || 0;
    }

    function costLabel(item) {
        const description = item.description || item.name || item.code || courier.value.toUpperCase();
        const service = item.service || item.type || '';
        const etd = item.etd ? `estimasi ${item.etd}` : '';
        const cost = formatCurrency(costValue(item));
        const serviceText = service ? `(${service})` : '';

        return `${description} ${serviceText} - ${etd} - ${cost}`;
    }

    lokasiKeyword.addEventListener('input', function () {
        clearTimeout(searchTimer);
        destination.value = '';
        lokasiLabel.value = '';
        layanan.innerHTML = '<option value="">Pilih lokasi pengiriman terlebih dahulu</option>';
        layanan.disabled = true;
        ongkir.value = '';
        grandTotal.textContent = formatCurrency(cartTotal);

        const keyword = this.value.trim();

        if (keyword.length < 3) {
            lokasiSelect.hidden = true;
            lokasiHelp.textContent = 'Ketik minimal 3 huruf lokasi tujuan.';
            return;
        }

        lokasiHelp.textContent = 'Mencari lokasi...';

        searchTimer = setTimeout(async function () {
            let result;

            try {
                const response = await fetch(`${destinationUrl}?keyword=${encodeURIComponent(keyword)}`);
                result = await response.json();
            } catch (error) {
                lokasiSelect.hidden = true;
                lokasiHelp.textContent = 'Gagal mengambil lokasi dari RajaOngkir.';
                return;
            }

            const locations = result.data || [];

            lokasiSelect.innerHTML = '';

            if (!result.success || locations.length === 0) {
                lokasiSelect.hidden = true;
                lokasiHelp.textContent = result.message || 'Lokasi tidak ditemukan.';
                return;
            }

            locations.forEach(function (item) {
                const option = document.createElement('option');
                option.value = destinationId(item);
                option.textContent = destinationLabel(item);
                lokasiSelect.appendChild(option);
            });

            lokasiSelect.hidden = false;
            lokasiHelp.textContent = 'Pilih salah satu lokasi dari daftar.';
        }, 500);
    });

    lokasiSelect.addEventListener('change', async function () {
        const selected = this.options[this.selectedIndex];

        destination.value = selected.value;
        lokasiLabel.value = selected.textContent;
        lokasiKeyword.value = selected.textContent;
        lokasiSelect.hidden = true;
        lokasiHelp.textContent = 'Mengambil layanan ongkir...';

        const formData = new FormData();
        formData.append('destination', destination.value);
        formData.append('courier', courier.value);
        formData.append('weight', weight.value);
        formData.append(csrfName, csrfHash);

        let result;

        try {
            const response = await fetch(costUrl, {
                method: 'POST',
                body: formData
            });
            result = await response.json();
        } catch (error) {
            layanan.disabled = true;
            layanan.innerHTML = '<option value="">Gagal mengambil layanan ongkir</option>';
            lokasiHelp.textContent = 'Gagal mengambil layanan ongkir dari RajaOngkir.';
            return;
        }

        if (result.csrfHash) {
            csrfHash = result.csrfHash;
            document.getElementById('csrfField').value = csrfHash;
        }
        const costs = result.data || [];

        layanan.innerHTML = '';

        if (!result.success || costs.length === 0) {
            layanan.disabled = true;
            layanan.innerHTML = '<option value="">Layanan ongkir tidak ditemukan</option>';
            lokasiHelp.textContent = result.message || 'Layanan ongkir tidak ditemukan.';
            return;
        }

        costs.forEach(function (item) {
            const value = costValue(item);
            const option = document.createElement('option');
            option.value = value;
            option.textContent = costLabel(item);
            option.dataset.label = option.textContent;
            layanan.appendChild(option);
        });

        layanan.disabled = false;
        layanan.dispatchEvent(new Event('change'));
        lokasiHelp.textContent = 'Lokasi dan layanan ongkir siap digunakan.';
    });

    layanan.addEventListener('change', function () {
        const selected = this.options[this.selectedIndex];
        const value = selected ? Number(selected.value) : 0;

        ongkir.value = value;
        layananLabel.value = selected ? selected.dataset.label || selected.textContent : '';
        grandTotal.textContent = formatCurrency(cartTotal + value);
    });
</script>

<?= $this->endSection() ?>
