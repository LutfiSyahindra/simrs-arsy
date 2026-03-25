<?php


use App\Http\Controllers\ProfileController;
use App\Http\Controllers\simrs\backOffice\keuangan\premiController;
use App\Http\Controllers\simrs\master\keuangan\gapokController;
use App\Http\Controllers\simrs\master\keuangan\jenisTunjanganController;
use App\Http\Controllers\simrs\master\keuangan\tunjanganPegawaiController;
use App\Http\Controllers\simrs\master\masterDataKeuanganController;
use App\Http\Controllers\simrs\Pelayanan\anjungan\anjunganAdmisiController;
use App\Http\Controllers\simrs\Pelayanan\anjungan\AnjunganController;
use App\Http\Controllers\simrs\Pelayanan\display\ApotekController;
use App\Http\Controllers\simrs\Pelayanan\display\DisplayAdmisiController;
use App\Http\Controllers\simrs\Pelayanan\display\IgdController;
use App\Http\Controllers\simrs\Pelayanan\display\KasirController;
use App\Http\Controllers\simrs\Pelayanan\display\PippController;
use App\Http\Controllers\simrs\Pelayanan\display\PoliWsController;
use App\Http\Controllers\simrs\Pelayanan\petugasPanggil\admisiPanggilController;
use App\Http\Controllers\simrs\Pelayanan\petugasPanggil\igdPanggilController;
use App\Http\Controllers\simrs\Pelayanan\petugasPanggil\kasirPanggilController;
use App\Http\Controllers\simrs\Pelayanan\petugasPanggil\loket\LoketAdmisiController;
use App\Http\Controllers\simrs\Pelayanan\petugasPanggil\pippPanggilController;
use App\Http\Controllers\simrs\Pelayanan\PetugasPanggil\poliPanggilController;
use App\Http\Controllers\simrs\Settings\Auth\PermissionsController;
use App\Http\Controllers\simrs\Settings\Auth\RoleController;
use App\Http\Controllers\simrs\Settings\Auth\UsersController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('simrs/dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('simrs/settings')->group(function () {
        // Users
        Route::get('/users', [UsersController::class, 'index'])->name('users.index');
        Route::get('/users/tableUsers', [UsersController::class, 'table'])->name('users.table');
        Route::put('/users/updateStatus', [UsersController::class, 'updateStatus'])->name('users.updateStatus');
        Route::get('/users/getBranches', [UsersController::class, 'getBranches'])->name('users.getBranches');
        Route::post('/users/store', [UsersController::class, 'store'])->name('users.store');
        Route::get('/users/{id}/edit', [UsersController::class, 'edit'])->name('users.edit');
        Route::put('/users/{id}/update', [UsersController::class, 'update'])->name('users.update');
        Route::delete('/users/{id}/delete', [UsersController::class, 'destroy'])->name('users.delete');
        Route::get('/users/dataRoles', [UsersController::class, 'dataRoles'])->name('users.dataRoles');
        Route::post('/users/assignRoles', [UsersController::class, 'assignRoles'])->name('users.assignRoles');
        Route::get('/users/{id}/getUserRoles', [UsersController::class, 'getUserRoles'])->name('users.getUserRoles');

        // Role
        Route::get('/roles', [RoleController::class, 'Role'])->name('roles.role');
        Route::get('/roles/tableRoles', [RoleController::class, 'table'])->name('roles.table');
        Route::post('/roles/store', [RoleController::class, 'store'])->name('roles.store');
        Route::get('/roles/{id}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{id}/update', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{id}/delete', [RoleController::class, 'destroy'])->name('roles.delete');
        Route::get('/roles/dataPermissions', [RoleController::class, 'dataPermissions'])->name('roles.dataPermissions');
        Route::post('/roles/assignPermissions', [RoleController::class, 'assignPermissions'])->name('roles.assignPermissions');
        Route::get('/roles/{id}/getRolePermissions', [RoleController::class, 'getRolePermissions'])->name('roles.getRolePermissions');

        // Permission
        Route::get('/permissions', [PermissionsController::class, 'Permissions'])->name('permissions.permissions');
        Route::get('/permissions/tablePermissions', [PermissionsController::class, 'table'])->name('permissions.table');
        Route::post('/permissions/store', [PermissionsController::class, 'store'])->name('permissions.store');
        Route::get('/permissions/{id}/edit', [PermissionsController::class, 'edit'])->name('permissions.edit');
        Route::put('/permissions/{id}/update', [PermissionsController::class, 'update'])->name('permissions.update');
        Route::delete('/permissions/{id}/delete', [PermissionsController::class, 'destroy'])->name('permissions.delete');
    });

    Route::prefix('simrs/pelayanan/display')->group(function () {
        Route::get('/display/poli-ws', [PoliWsController::class, 'index'])->name('pelayanan.display.poliws');
        Route::get('/display/pipp', [PippController::class, 'index'])->name('pelayanan.display.pipp');
        Route::get('/display/kasir-ws', [KasirController::class, 'index'])->name('pelayanan.display.kasir');
        Route::get('/display/anjungan', [AnjunganController::class, 'index'])->name('pelayanan.display.anjungan');
        Route::get('/display/igd', [IgdController::class, 'index'])->name('pelayanan.display.igd');

        // Display Apotek
        Route::get('/display/apotek', [ApotekController::class, 'index'])->name('pelayanan.display.apotek');
        Route::get('/display/panggil-apotek', [ApotekController::class, 'panggilAntrean'])->name('pelayanan.display.panggilapotek');
        Route::put('/display/update-apotek', [ApotekController::class, 'updateAntrean'])->name('pelayanan.display.updateapotek');
        Route::get('/display/nonracikan', [ApotekController::class, 'dataNonracikan'])->name('pelayanan.display.nonracikan');
        Route::get('/display/racikan', [ApotekController::class, 'dataracikan'])->name('pelayanan.display.racikan');

        // Display Admisi
        Route::get('/display/admisi', [DisplayAdmisiController::class, 'index'])->name('pelayanan.display.admisi');
    });

    Route::prefix('simrs/pelayanan/petugasPanggil')->group(function () {
        // Poli WS
        Route::get('/petugasPanggilpoli-ws', [poliPanggilController::class, 'poliPanggil'])->name('pelayanan.petugasPanggil.poliws');
        Route::get('/petugasPanggilpoli-ws/getDataPoli', [poliPanggilController::class, 'getDataPoli'])->name('pelayanan.petugasPanggil.getDataPoli');
        Route::get('/petugasPanggilpoli-ws/getDataDokter', [poliPanggilController::class, 'getDataDokter'])->name('pelayanan.petugasPanggil.getDataDokter');
        Route::get('/petugasPanggilpoli-ws/getDataPasien', [poliPanggilController::class, 'getDataPasien'])->name('pelayanan.petugasPanggil.getDataPasien');
        Route::post('/petugasPanggilpoli-ws/panggilPasien', [poliPanggilController::class, 'panggilPasien'])->name('pelayanan.petugasPanggil.panggilPasien');
        
        // PIPP
        Route::get('/petugasPanggilpipp/pippPanggil', [pippPanggilController::class, 'index'])->name('pelayanan.petugasPanggil.pipp.pippPanggil');
        Route::get('/petugasPanggilpipp/dataPasien', [pippPanggilController::class, 'getDataPasien'])->name('pelayanan.petugasPanggil.pipp.pippPanggil.dataPasien');
        Route::post('/petugasPanggilpipp/panggilPipp', [pippPanggilController::class, 'panggilPipp'])->name('pelayanan.petugasPanggil.pipp.pippPanggil.panggilPipp');

        // Kasir WS
        Route::get('/petugasPanggilkasir/kasirWs', [kasirPanggilController::class, 'index'])->name('pelayanan.petugasPanggil.kasir.kasirWs');
        Route::get('/petugasPanggilkasir/getDataPasien', [kasirPanggilController::class, 'getDataPasien'])->name('pelayanan.petugasPanggil.kasir.getDataPasien');
        Route::post('/petugasPanggilkasir/panggilKasir', [kasirPanggilController::class, 'panggilKasir'])->name('pelayanan.petugasPanggil.kasir.panggilKasir');

        // Admisi Panggil & Loket Admisi
        Route::get('/petugasPanggildmisi/admisiPanggil', [admisiPanggilController::class, 'index'])->name('petugasPanggil.admisi.admisiPanggil');
        Route::get('/petugasPanggiladmisi/admisiPanggil/dataPasien', [admisiPanggilController::class, 'getDataPasien'])->name('petugasPanggil.admisi.admisiPanggil.dataPasien');
        Route::post('/petugasPanggiladmisi/admisiPanggil/panggilAdmisi', [admisiPanggilController::class, 'panggilAdmisi'])->name('petugasPanggil.admisi.admisiPanggil.panggilAdmisi');

        // Admisi
        Route::get('/loket/admisi/getAllLoket', [LoketAdmisiController::class, 'getAllLoket'])->name('loket.admisi.getAllLoket');
        Route::post('/loket/admisi/lockLoket', [LoketAdmisiController::class, 'lockLoket'])->name('loket.admisi.lockLoket');
        Route::post('/loket/admisi/unlockLoket', [LoketAdmisiController::class, 'unlockLoket'])->name('loket.admisi.unlockLoket');
        Route::get('/loket/admisi/getLoketAktif', [LoketAdmisiController::class, 'getLoketAktif'])->name('loket.admisi.getLoketAktif');

        // Igd
        Route::get('/petugasPanggiligd/igdPanggil', [igdPanggilController::class, 'index'])->name('pelayanan.petugasPanggil.igd.igdPanggil');
        Route::get('/petugasPanggiligd/dataPasien', [igdPanggilController::class, 'getDataPasienIgd'])->name('pelayanan.petugasPanggil.igd.igdPanggil.dataPasien');
        Route::post('/petugasPanggiligd/panggilIgd', [igdPanggilController::class, 'panggilIgd'])->name('pelayanan.petugasPanggil.igd.igdPanggil.panggilIgd');
    });

    Route::prefix('simrs/backOffice/keuangan')->group(function () {
        Route::get('/premi', [premiController::class, 'index'])->name('backOffice.keuangan.premi');
        Route::get('/premi/getPremiTable', [premiController::class, 'getPremiTable'])->name('backOffice.keuangan.premi.getPremiTable');
        Route::get('/premi/getPremiDetail', [premiController::class, 'getPremiDetail'])->name('backOffice.keuangan.premi.getPremiDetail');
        Route::get('/premi/getPremiDokterChart', [premiController::class, 'getPremiDokterChart'])->name('backOffice.keuangan.premi.getPremiDokterChart');
        Route::get('/premi/getPremiDokterSummary', [premiController::class, 'getPremiDokterSummary'])->name('backOffice.keuangan.premi.getPremiDokterSummary');
        Route::get('/premi/getPremiDokterChartOverlay', [premiController::class, 'getPremiDokterChartOverlay'])->name('backOffice.keuangan.premi.getPremiDokterChartOverlay');
        Route::get('/premi/getPremiParamedisChart', [premiController::class, 'getPremiParamedisChart'])->name('backOffice.keuangan.premi.getPremiParamedisChart');
        Route::get('/premi/getPremiParamedisSummary', [premiController::class, 'getPremiParamedisSummary'])->name('backOffice.keuangan.premi.getPremiParamedisSummary');
        Route::get('/premi/getPremiParamedisChartOverlay', [premiController::class, 'getPremiParamedisChartOverlay'])->name('backOffice.keuangan.premi.getPremiParamedisChartOverlay');
        Route::get('/premi/getPremiKamarChart', [premiController::class, 'getPremiKamarChart'])->name('backOffice.keuangan.premi.getPremiKamarChart');
        Route::get('/premi/getPremiKamarSummary', [premiController::class, 'getPremiKamarSummary'])->name('backOffice.keuangan.premi.getPremiKamarSummary');
        Route::get('/premi/getPremiKamarChartOverlay', [premiController::class, 'getPremiKamarChartOverlay'])->name('backOffice.keuangan.premi.getPremiKamarChartOverlay');
        Route::get('/premi/cetakPremiDetailPdf', [premiController::class, 'cetakPremiDetailPdf'])->name('backOffice.keuangan.premi.cetakPremiDetailPdf');
        Route::get('/premi/cetakPremiDetailExcel', [premiController::class, 'cetakPremiDetailExcel'])->name('backOffice.keuangan.premi.cetakPremiDetailExcel');
        Route::get('/premi/cetakPremiAllPdf', [premiController::class, 'cetakAllPremiPdf'])->name('backOffice.keuangan.premi.cetakPremiAllPdf');
    });

    Route::prefix('simrs/masterData/keuangan')->group(function () {
        Route::get('/gapok', [gapokController::class, 'index'])->name('masterData.keuangan.gapok');
        Route::get('/gapok/getGapokTable', [gapokController::class, 'getGapokTable'])->name('masterData.keuangan.gapok.getGapokTable');
        Route::get('/gapok/exportTemplate', [gapokController::class, 'exportTemplate'])->name('masterData.keuangan.gapok.exportTemplate');
        Route::post('/gapok/import', [gapokController::class, 'importGapok'])->name('masterData.keuangan.gapok.import');
        Route::post('/gapok/store', [gapokController::class, 'store'])->name('masterData.keuangan.gapok.store');
        Route::get('/gapok/{id}/edit', [gapokController::class, 'edit'])->name('masterData.keuangan.gapok.edit');
        Route::put('/gapok/{id}/update', [gapokController::class, 'update'])->name('masterData.keuangan.gapok.update');
        Route::delete('/gapok/{id}/delete', [gapokController::class, 'destroy'])->name('masterData.keuangan.gapok.delete');

        Route::get('/tunjangan', [jenisTunjanganController::class, 'index'])->name('masterData.keuangan.tunjangan');
        Route::get('/tunjangan/generateKode', [jenisTunjanganController::class, 'generateKodeTunjangan'])->name('masterData.keuangan.tunjangan.generateKode');
        Route::get('/tunjangan/getTunjanganTable', [jenisTunjanganController::class, 'getJnsTunjanganTable'])->name('masterData.keuangan.tunjangan.getJnsTunjanganTable');
        Route::get('/tunjangan/exportTemplate', [jenisTunjanganController::class, 'exportTemplate'])->name('masterData.keuangan.tunjangan.exportTemplate');
        Route::post('/tunjangan/import', [jenisTunjanganController::class, 'importTunjangan'])->name('masterData.keuangan.tunjangan.import');
        Route::post('/tunjangan/store', [jenisTunjanganController::class, 'store'])->name('masterData.keuangan.tunjangan.store');
        Route::get('/tunjangan/{id}/edit', [jenisTunjanganController::class, 'edit'])->name('masterData.keuangan.tunjangan.edit');
        Route::put('/tunjangan/{id}/update', [jenisTunjanganController::class, 'update'])->name('masterData.keuangan.tunjangan.update');
        Route::delete('/tunjangan/{id}/delete', [jenisTunjanganController::class, 'destroy'])->name('masterData.keuangan.tunjangan.delete');

        Route::get('/tunjanganPegawai', [tunjanganPegawaiController::class, 'index'])->name('masterData.keuangan.tunjanganPegawai');
        Route::get('/tunjanganPegawai/getTunjanganPegawaiTable', [tunjanganPegawaiController::class, 'getTunjanganPegawaiTable'])->name('masterData.keuangan.tunjangan.gettunjnaganPegawaiTable');
        Route::get('/tunjanganPegawai/guideJenisTunjangan', [tunjanganPegawaiController::class, 'guideJenisTunjangan'])->name('masterData.keuangan.tunjangan.getGuideJenisTunjangan');
        Route::get('/tunjanganPegawai/exportTemplate', [tunjanganPegawaiController::class, 'exportTemplate'])->name('masterData.keuangan.tunjangan.getTunjanganPegawaiExportTemplate');
        Route::post('/tunjanganPegawai/import', [tunjanganPegawaiController::class, 'importTunjanganPegawai'])->name('masterData.keuangan.tunjangan.importTunjanganPegawai');
        Route::get('/tunjanganPegawai/getPegawai', [tunjanganPegawaiController::class, 'getPegawai'])->name('masterData.keuangan.tunjangan.getPegawai');
        Route::post('/tunjanganPegawai/store', [tunjanganPegawaiController::class, 'store'])->name('masterData.keuangan.tunjanganPegawai.store');
        Route::put('/tunjangan/update-inline/{id}',[TunjanganPegawaiController::class, 'updateInline'])->name('masterData.keuangan.tunjanganPegawai.updateInline');
        Route::put('/tunjangan/bulk-update', [TunjanganPegawaiController::class, 'bulkUpdate'])->name('masterData.keuangan.tunjanganPegawai.bulkUpdate');
        Route::delete('/tunjanganPegawai/{id}/delete', [tunjanganPegawaiController::class, 'destroy'])->name('masterData.keuangan.tunjanganPegawai.delete');
        Route::get('/tunjangan/by-pegawai/{nik}',[TunjanganPegawaiController::class, 'getByPegawai'])->name('masterData.keuangan.tunjanganPegawai.getByPegawai');
        Route::post('/tunjangan/distribusi',[TunjanganPegawaiController::class, 'distribusi'])->name('masterData.keuangan.tunjangan.distribusi');
        Route::post('/tunjangan/preview-distribusi',[TunjanganPegawaiController::class, 'previewDistribusi'])->name('masterData.keuangan.tunjangan.previewDistribusi');
    });
});

require __DIR__.'/auth.php';
