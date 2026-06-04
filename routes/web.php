<?php


use App\Http\Controllers\ProfileController;
use App\Http\Controllers\simrs\backOffice\keuangan\penggajianController;
use App\Http\Controllers\simrs\backOffice\keuangan\premiController;
use App\Http\Controllers\simrs\master\keuangan\gapokController;
use App\Http\Controllers\simrs\master\keuangan\jabatanController;
use App\Http\Controllers\simrs\master\keuangan\jenisTunjanganController;
use App\Http\Controllers\simrs\master\keuangan\jnsTindakanController;
use App\Http\Controllers\simrs\master\keuangan\profesiController;
use App\Http\Controllers\simrs\master\keuangan\skorController;
use App\Http\Controllers\simrs\master\keuangan\tunjanganPegawaiController;
use App\Http\Controllers\simrs\master\keuangan\unitController;
use App\Http\Controllers\simrs\master\mapping\mappingSkorController;
use App\Http\Controllers\simrs\master\mapping\mappingTindakanController;
use App\Http\Controllers\simrs\master\mapping\mappingUnitController;
use App\Http\Controllers\simrs\master\mapping\masppingController;
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
        Route::get('/premi/getPremiRsChart', [premiController::class, 'getPremiRsChart'])->name('backOffice.keuangan.premi.getPremiRsChart');
        Route::get('/premi/getPremiRsSummary', [premiController::class, 'getPremiRsSummary'])->name('backOffice.keuangan.premi.getPremiRsSummary');
        Route::get('/premi/getPremiRsChartOverlay', [premiController::class, 'getPremiRsChartOverlay'])->name('backOffice.keuangan.premi.getPremiRsChartOverlay');
        Route::get('/premi/cetakPremiDetailPdf', [premiController::class, 'cetakPremiDetailPdf'])->name('backOffice.keuangan.premi.cetakPremiDetailPdf');
        Route::get('/premi/cetakPremiDetailExcel', [premiController::class, 'cetakPremiDetailExcel'])->name('backOffice.keuangan.premi.cetakPremiDetailExcel');
        Route::get('/premi/cetakPremiAllPdf', [premiController::class, 'cetakAllPremiPdf'])->name('backOffice.keuangan.premi.cetakPremiAllPdf');

        Route::get('/penggajian', [penggajianController::class, 'index'])->name('backOffice.keuangan.penggajian');
        Route::get('/penggajian/getGajiTahap1Table', [penggajianController::class, 'getGajiTahap1Table'])->name('backOffice.keuangan.penggajian.getGajiTahap1Table');
        Route::get('/penggajian/getPenggajianDetail', [penggajianController::class, 'getPenggajianDetail'])->name('backOffice.keuangan.penggajian.getPenggajianDetail');
        Route::post('/penggajian/generateGajiTahap1', [penggajianController::class, 'generateGajiTahap1'])->name('backOffice.keuangan.penggajian.generateGajiTahap1');
        Route::get('/penggajian/getSummaryGajiTahap1', [penggajianController::class, 'getSummaryGajiTahap1'])->name('backOffice.keuangan.penggajian.getSummaryGajiTahap1');
        Route::get('/penggajian/gajitahap1/{id}/detail', [PenggajianController::class, 'detailGajiTahap1'])->name('backOffice.keuangan.penggajian.detailGajiTahap1');
        Route::get('/penggajian/gajitahap1/{id}/export-pdf', [PenggajianController::class, 'exportSlipGajiTahap1Pdf'])->name('backOffice.keuangan.penggajian.exportSlipGajiTahap1Pdf');
        
    });

    Route::prefix('simrs/masterData/keuangan')->group(function () {
        Route::get('/gapok', [gapokController::class, 'index'])->name('masterData.keuangan.gapok');
        Route::get('/gapok/getGapokTable', [gapokController::class, 'getGapokTable'])->name('masterData.keuangan.gapok.getGapokTable');
        Route::get('/gapok/getPegawai', [gapokController::class, 'getPegawai'])->name('masterData.keuangan.gapok.getPegawai');
        Route::get('/gapok/getPegawaiByNik/{nik}', [gapokController::class, 'getPegawaiByNik'])->name('masterData.keuangan.gapok.getPegawaiByNik');
        Route::post('/gapok/syncGapok', [gapokController::class, 'syncGapok'])->name('masterData.keuangan.gapok.syncGapok');
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
        Route::get('/tunjanganPegawai/getJabatan', [tunjanganPegawaiController::class, 'getJabatan'])->name('masterData.keuangan.tunjanganPegawai.getJabatan');
        Route::get('/tunjanganPegawai/getProfesi', [tunjanganPegawaiController::class, 'getProfesi'])->name('masterData.keuangan.tunjanganPegawai.getProfesi');
        Route::get('/tunjanganPegawai/getGapokById/{nik}', [tunjanganPegawaiController::class, 'getGapokById'])->name('masterData.keuangan.tunjanganPegawai.getGapokById');

        Route::get('/jabatan', [jabatanController::class, 'index'])->name('masterData.keuangan.jabatan');
        Route::get('/jabatan/getJabatanTable', [jabatanController::class, 'jabatanTable'])->name('masterData.keuangan.jabatan.getJabatanTable');
        Route::get('/jabatan/generateKode', [jabatanController::class, 'generateKodeJabatan'])->name('masterData.keuangan.jabatan.generateKode');
        Route::post('/jabatan/store', [jabatanController::class, 'store'])->name('masterData.keuangan.jabatan.store');
        Route::get('/jabatan/{id}/edit', [jabatanController::class, 'edit'])->name('masterData.keuangan.jabatan.edit');
        Route::put('/jabatan/{id}/update', [jabatanController::class, 'update'])->name('masterData.keuangan.jabatan.update');
        Route::delete('/jabatan/{id}/delete', [jabatanController::class, 'destroy'])->name('masterData.keuangan.jabatan.delete');

        Route::get('/profesi', [profesiController::class, 'index'])->name('masterData.keuangan.profesi');
        Route::get('/profesi/getProfesiTable', [profesiController::class, 'profesiTable'])->name('masterData.keuangan.profesi.getProfesiTable');
        Route::get('/profesi/generateKode', [profesiController::class, 'generateKodeProfesi'])->name('masterData.keuangan.profesi.generateKode');
        Route::post('/profesi/store', [profesiController::class, 'store'])->name('masterData.keuangan.profesi.store');
        Route::get('/profesi/{id}/edit', [profesiController::class, 'edit'])->name('masterData.keuangan.profesi.edit');
        Route::put('/profesi/{id}/update', [profesiController::class, 'update'])->name('masterData.keuangan.profesi.update');
        Route::delete('/profesi/{id}/delete', [profesiController::class, 'destroy'])->name('masterData.keuangan.profesi.delete');

        Route ::get('/skor', [skorController::class, 'skor'])->name('masterData.keuangan.skor');
        Route::get('/skor/getSkorTable', [skorController::class, 'skorTable'])->name('masterData.keuangan.skor.getSkorTable');
        Route::get('/skor/generateKode', [skorController::class, 'generateKodeSkor'])->name('masterData.keuangan.skor.generateKode');
        Route::post('/skor/store', [skorController::class, 'store'])->name('masterData.keuangan.skor.store');
        Route::get('/skor/{id}/edit', [skorController::class, 'edit'])->name('masterData.keuangan.skor.edit');
        Route::put('/skor/{id}/update', [skorController::class, 'update'])->name('masterData.keuangan.skor.update');
        Route::delete('/skor/{id}/delete', [skorController::class, 'destroy'])->name('masterData.keuangan.skor.delete');

        Route::get('/unit', [unitController::class, 'unit'])->name('masterData.keuangan.unit');
        Route::get('/unit/getUnitTable', [unitController::class, 'unitTable'])->name('masterData.keuangan.unit.getUnitTable');
        Route::get('/unit/generateKode', [unitController::class, 'generateKodeUnit'])->name('masterData.keuangan.unit.generateKode');
        Route::post('/unit/store', [unitController::class, 'store'])->name('masterData.keuangan.unit.store');
        Route::get('/unit/{id}/edit', [unitController::class, 'edit'])->name('masterData.keuangan.unit.edit');
        Route::put('/unit/{id}/update', [unitController::class, 'update'])->name('masterData.keuangan.unit.update');
        Route::delete('/unit/{id}/delete', [unitController::class, 'destroy'])->name('masterData.keuangan.unit.delete');

        Route::get('/jnsTindakan', [jnsTindakanController::class, 'jnsTindakan'])->name('masterData.keuangan.jnsTindakan');
        Route::get('/jnsTindakan/getJnsTindakanTable', [jnsTindakanController::class, 'jnsTindakanTable'])->name('masterData.keuangan.jnsTindakan.getJnsTindakanTable');
        Route::get('/jnsTindakan/generateKode', [jnsTindakanController::class, 'generateKodeJnsTindakan'])->name('masterData.keuangan.jnsTindakan.generateKode');
        Route::post('/jnsTindakan/store', [jnsTindakanController::class, 'store'])->name('masterData.keuangan.jnsTindakan.store');
        Route::get('/jnsTindakan/{id}/edit', [jnsTindakanController::class, 'edit'])->name('masterData.keuangan.jnsTindakan.edit');
        Route::put('/jnsTindakan/{id}/update', [jnsTindakanController::class, 'update'])->name('masterData.keuangan.jnsTindakan.update');
        Route::delete('/jnsTindakan/{id}/delete', [jnsTindakanController::class, 'destroy'])->name('masterData.keuangan.jnsTindakan.delete');
    });

    Route::prefix('simrs/masterData/mapping')->group(function () {
        Route::get('/mapping', [masppingController::class, 'mapping'])->name('masterData.mapping');

        Route::get('/mappingSkor', [mappingSkorController::class, 'mappingSkor'])->name('masterData.mapping.mappingSkor.mappingSkor');
        Route::get('/mappingSkor/getSkorTable', [mappingSkorController::class, 'skorTable'])->name('masterData.mapping.mappingSkor.getSkorTable');
        Route::get('/mappingSkor/getPegawai', [mappingSkorController::class, 'getPegawai'])->name('masterData.mapping.mappingSkor.getPegawai');
        Route::get('/mappingSkor/guideSkor', [mappingSkorController::class, 'guideSkor'])->name('masterData.mapping.mappingSkor.guideSkor');
        Route::get('/mappingSkor/by-pegawai/{nik}', [mappingSkorController::class, 'getByPegawai'])->name('masterData.mapping.mappingSkor.getByPegawai');
        Route::get('/mappingSkor/getGapokById/{nik}', [mappingSkorController::class, 'getGapokById'])->name('masterData.mapping.mappingSkor.getGapokById');
        Route::get('/mappingSkor/generateKode', [mappingSkorController::class, 'generateKodeSkor'])->name('masterData.mapping.mappingSkor.generateKode');
        Route::post('/mappingSkor/store', [mappingSkorController::class, 'store'])->name('masterData.mapping.mappingSkor.store');
        Route::get('/mappingSkor/{id}/edit', [mappingSkorController::class, 'edit'])->name('masterData.mapping.mappingSkor.edit');
        Route::put('/mappingSkor/{id}/update', [mappingSkorController::class, 'update'])->name('masterData.mapping.mappingSkor.update');
        Route::delete('/mappingSkor/{id}/delete', [mappingSkorController::class, 'destroy'])->name('masterData.mapping.mappingSkor.delete');
        Route::get('/mappingSkor/exportTemplate', [mappingSkorController::class, 'exportTemplate'])->name('masterData.mapping.mappingSkor.exportTemplate');
        Route::post('/mappingSkor/import', [mappingSkorController::class, 'importMappingSkor'])->name('masterData.mapping.mappingSkor.importMappingSkor');

        Route::get('/mappingUnit', [mappingUnitController::class, 'mappingUnit'])->name('masterData.mapping.mappingUnit.mappingUnit');
        Route::get('/mappingUnit/getUnitTable', [mappingUnitController::class, 'unitTable'])->name('masterData.mapping.mappingUnit.getUnitTable');
        Route::get('/mappingUnit/getPegawai', [mappingUnitController::class, 'getPegawai'])->name('masterData.mapping.mappingUnit.getPegawai');
        Route::get('/mappingUnit/guideUnit', [mappingUnitController::class, 'guideUnit'])->name('masterData.mapping.mappingUnit.guideUnit');
        Route::get('/mappingUnit/by-pegawai/{nik}', [mappingUnitController::class, 'getByPegawaiUnit'])->name('masterData.mapping.mappingUnit.getByPegawaiUnit');
        Route::get('/mappingUnit/getGapokById/{nik}', [mappingUnitController::class, 'getGapokById'])->name('masterData.mapping.mappingUnit.getGapokById');
        Route::get('/mappingUnit/generateKode', [mappingUnitController::class, 'generateKodeUnit'])->name('masterData.mapping.mappingUnit.generateKode');
        Route::post('/mappingUnit/store', [mappingUnitController::class, 'storeUnit'])->name('masterData.mapping.mappingUnit.store');
        Route::get('/mappingUnit/{id}/edit', [mappingUnitController::class, 'editUnit'])->name('masterData.mapping.mappingUnit.edit');
        Route::put('/mappingUnit/{id}/update', [mappingUnitController::class, 'updateUnit'])->name('masterData.mapping.mappingUnit.update');
        Route::delete('/mappingUnit/{id}/delete', [mappingUnitController::class, 'destroyUnit'])->name('masterData.mapping.mappingUnit.delete');
        Route::get('/mappingUnit/exportTemplate', [mappingUnitController::class, 'exportTemplateUnit'])->name('masterData.mapping.mappingUnit.exportTemplate');
        Route::post('/mappingUnit/import', [mappingUnitController::class, 'importMappingUnit'])->name('masterData.mapping.mappingUnit.importMappingUnit');

        Route::get('/mappingTindakan', [mappingTindakanController::class, 'mappingTindakan'])->name('masterData.mapping.mappingTindakan');
        Route::get('/mappingTindakan/getTindakanTable', [mappingTindakanController::class, 'tindakanTable'])->name('masterData.mapping.mappingTindakan.getTindakanTable');
        Route::get('/mappingTindakan/guideJenisTindakan', [mappingTindakanController::class, 'guideJenisTindakan'])->name('masterData.mapping.mappingTindakan.guideJenisTindakan');
        Route::get('/mappingTindakan/searchTindakan', [mappingTindakanController::class, 'searchTindakan'])->name('masterData.mapping.mappingTindakan.searchTindakan');
        Route::get('/mappingTindakan/by-jenis/{id}', [mappingTindakanController::class, 'getByJenisTindakan'])->name('masterData.mapping.mappingTindakan.byJenis');
        Route::post('/mappingTindakan/store', [mappingTindakanController::class, 'storeTindakan'])->name('masterData.mapping.mappingTindakan.store');
        Route::get('/mappingTindakan/{id}/edit', [mappingTindakanController::class, 'editTindakan'])->name('masterData.mapping.mappingTindakan.edit');
        Route::put('/mappingTindakan/{id}/update', [mappingTindakanController::class, 'updateTindakan'])->name('masterData.mapping.mappingTindakan.update');
        Route::delete('/mappingTindakan/{id}/delete', [mappingTindakanController::class, 'destroyTindakan'])->name('masterData.mapping.mappingTindakan.delete');
    });
});

require __DIR__.'/auth.php';
