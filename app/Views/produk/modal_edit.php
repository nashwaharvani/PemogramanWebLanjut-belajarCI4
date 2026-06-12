<?php foreach ($products as $index => $produk) : ?>    
    <!-- Edit Modal Begin -->
    <div class="modal fade" id="editModal-<?= $produk['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <?= form_open_multipart(site_url('produk/edit/' . $produk['id'])); ?>
                <?= csrf_field(); ?>

                <div class="modal-body">
                    <div class="mb-3">
                        <?= form_label('Nama', 'nama-' . $produk['id']); ?>
                        <?= form_input([
                            'name'        => 'nama',
                            'id'          => 'nama-' . $produk['id'],
                            'class'       => 'form-control',
                            'value'       => $produk['nama'],
                            'placeholder' => 'Nama Barang',
                            'required'    => true
                        ]); ?>
                    </div>

                    <div class="mb-3">
                        <?= form_label('Harga', 'harga-' . $produk['id']); ?>
                        <?= form_input([
                            'name'        => 'harga',
                            'id'          => 'harga-' . $produk['id'],
                            'class'       => 'form-control',
                            'value'       => $produk['harga'],
                            'placeholder' => 'Harga Barang',
                            'required'    => true
                        ]); ?>
                    </div>

                    <div class="mb-3">
                        <?= form_label('Jumlah', 'jumlah-' . $produk['id']); ?>
                        <?= form_input([
                            'type'        => 'number', 
                            'name'        => 'jumlah',
                            'id'          => 'jumlah-' . $produk['id'],
                            'class'       => 'form-control',
                            'value'       => $produk['jumlah'],
                            'placeholder' => 'Jumlah Barang',
                            'required'    => true
                        ]); ?>
                    </div>

                    <?php if ($produk['foto'] != '' and file_exists('img/' . $produk['foto'])) : ?>
                        <div class="mb-3">
                            <img src="<?= base_url('img/' . $produk['foto']); ?>" width="100">
                        </div>
                    <?php endif; ?>

                    <div class="form-check mb-3">
                        <?= form_checkbox([
                            'name'    => 'check',
                            'id'      => 'check-' . $produk['id'],
                            'value'   => '1',
                            'class'   => 'form-check-input'
                        ]); ?>

                        <?= form_label(
                            'Ceklis jika ingin mengganti foto',
                            'check-' . $produk['id'],
                            ['class' => 'form-check-label']
                        ); ?>
                    </div>

                    <div class="mb-3">
                        <?= form_label('Foto', 'foto-' . $produk['id']); ?>
                        <?= form_upload([
                            'name'  => 'foto',
                            'id'    => 'foto-' . $produk['id'],
                            'class' => 'form-control'
                        ]); ?>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Close
                    </button>

                    <?= form_submit('submit', 'Simpan', ['class' => 'btn btn-primary']); ?>
                </div>

                <?= form_close(); ?>
            </div>
        </div>
    </div>
    <!-- Edit Modal End -->
<?php endforeach ?>
