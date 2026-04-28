<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<div class="row">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body profile-card pt-4 d-flex flex-column align-items-center">
                <img src="<?= base_url() ?>NiceAdmin/assets/img/profile-img.jpg" alt="Profile" class="rounded-circle" width="120" style="margin-bottom: 15px;">
                <h2><?= session()->get('username') ?></h2>
                <h3><?= ucfirst(session()->get('role')) ?></h3>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card">
            <div class="card-body pt-3">
                <!-- Bordered Tabs -->
                <ul class="nav nav-tabs nav-tabs-bordered">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#profile-overview">Overview</button>
                    </li>
                </ul>
                <div class="tab-content pt-2">
                    <div class="tab-pane fade show active profile-overview" id="profile-overview">
                        <h5 class="card-title">Profile Details</h5>

                        <div class="row mb-3">
                            <div class="col-lg-3 col-md-4 label">Username</div>
                            <div class="col-lg-9 col-md-8"><?= session()->get('username') ?></div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-lg-3 col-md-4 label">Role</div>
                            <div class="col-lg-9 col-md-8"><?= ucfirst(session()->get('role')) ?></div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-lg-3 col-md-4 label">Email</div>
                            <div class="col-lg-9 col-md-8"><?= session()->get('email') ?></div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-lg-3 col-md-4 label">Waktu Login</div>
                            <div class="col-lg-9 col-md-8"><?= session()->get('waktu_login') ?></div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-lg-3 col-md-4 label">Status Login</div>
                            <div class="col-lg-9 col-md-8">
                                <?php if (session()->get('isLoggedIn')) : ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else : ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div><!-- End Bordered Tabs -->
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
