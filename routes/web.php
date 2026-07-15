<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\simrs\backOffice\keuangan\hitungPremiApotekController;
use App\Http\Controllers\simrs\backOffice\keuangan\hitungPremiBersamaController;
use App\Http\Controllers\simrs\backOffice\keuangan\hitungPremiBhpController;
use App\Http\Controllers\simrs\backOffice\keuangan\hitungPremiCasemixController;
use App\Http\Controllers\simrs\backOffice\keuangan\hitungPremiController;
use App\Http\Controllers\simrs\backOffice\keuangan\hitungPremiDokterController;
use App\Http\Controllers\simrs\backOffice\keuangan\hitungPremiDriverController;
use App\Http\Controllers\simrs\backOffice\keuangan\hitungPremiFisioController;
use App\Http\Controllers\simrs\backOffice\keuangan\hitungPremiGiziController;
use App\Http\Controllers\simrs\backOffice\keuangan\hitungPremiIcuController;
use App\Http\Controllers\simrs\backOffice\keuangan\hitungPremiKamarController;
use App\Http\Controllers\simrs\backOffice\keuangan\hitungPremiLaboratoriumController;
use App\Http\Controllers\simrs\backOffice\keuangan\hitungPremiNicuController;
use App\Http\Controllers\simrs\backOffice\keuangan\hitungPremiOperasiController;
use App\Http\Controllers\simrs\backOffice\keuangan\hitungPremiPelayananNonMedisController;
use App\Http\Controllers\simrs\backOffice\keuangan\hitungPremiRadiologiController;
use App\Http\Controllers\simrs\backOffice\keuangan\hitungPremiTindakanMedisController;
use App\Http\Controllers\simrs\backOffice\keuangan\hitungPremiUgdController;
use App\Http\Controllers\simrs\backOffice\keuangan\hitungPremiVkController;
use App\Http\Controllers\simrs\backOffice\keuangan\keuanganController;
use App\Http\Controllers\simrs\backOffice\keuangan\penggajianController;
use App\Http\Controllers\simrs\backOffice\keuangan\premiController;
use App\Http\Controllers\simrs\master\keuangan\gapokController;
use App\Http\Controllers\simrs\master\keuangan\jabatanController;
use App\Http\Controllers\simrs\master\keuangan\jenisPotonganController;
use App\Http\Controllers\simrs\master\keuangan\jenisTunjanganController;
use App\Http\Controllers\simrs\master\keuangan\jnsPremiController;
use App\Http\Controllers\simrs\master\keuangan\jnsTindakanController;
use App\Http\Controllers\simrs\master\keuangan\plotingPremiController;
use App\Http\Controllers\simrs\master\keuangan\potonganPegawaiController;
use App\Http\Controllers\simrs\master\keuangan\profesiController;
use App\Http\Controllers\simrs\master\keuangan\skorController;
use App\Http\Controllers\simrs\master\keuangan\tunjanganPegawaiController;
use App\Http\Controllers\simrs\master\keuangan\unitController;
use App\Http\Controllers\simrs\master\mapping\mappingPremiController;
use App\Http\Controllers\simrs\master\mapping\mappingSkorController;
use App\Http\Controllers\simrs\master\mapping\mappingTindakanController;
use App\Http\Controllers\simrs\master\mapping\mappingUnitController;
use App\Http\Controllers\simrs\master\mapping\masppingController;
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
        Route::get('/dashboard', [keuanganController::class, 'index'])->name('backOffice.keuangan.dashboard');

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

        Route::get('/hitung-premi', [hitungPremiController::class, 'index'])->name('backOffice.keuangan.hitungPremi');
        Route::get('/hitung-premi/generator-status', [hitungPremiController::class, 'generatorStatus'])->name('backOffice.keuangan.hitungPremi.generatorStatus');
        Route::get('/hitung-premi/generateBhp', [hitungPremiBhpController::class, 'generateBhp'])->name('backOffice.keuangan.hitungPremi.generateBhp');
        Route::get('/hitung-premi/generateBhp/table', [hitungPremiBhpController::class, 'table'])->name('backOffice.keuangan.hitungPremi.generateBhp.table');
        Route::get('/hitung-premi/generateBhp/summary', [hitungPremiBhpController::class, 'summary'])->name('backOffice.keuangan.hitungPremi.generateBhp.summary');
        Route::get('/hitung-premi/generateBhp/config', [hitungPremiBhpController::class, 'config'])->name('backOffice.keuangan.hitungPremi.generateBhp.config');
        Route::put('/hitung-premi/generateBhp/config', [hitungPremiBhpController::class, 'updateConfig'])->name('backOffice.keuangan.hitungPremi.generateBhp.updateConfig');
        Route::post('/hitung-premi/generateBhp', [hitungPremiBhpController::class, 'store'])->name('backOffice.keuangan.hitungPremi.generateBhp.store');
        Route::post('/hitung-premi/generateBhp/lock-all', [hitungPremiBhpController::class, 'lockAll'])->name('backOffice.keuangan.hitungPremi.generateBhp.lockAll');
        Route::get('/hitung-premi/generateBhp/{id}/detail', [hitungPremiBhpController::class, 'detail'])->name('backOffice.keuangan.hitungPremi.generateBhp.detail');
        Route::post('/hitung-premi/generateBhp/{id}/lock', [hitungPremiBhpController::class, 'lock'])->name('backOffice.keuangan.hitungPremi.generateBhp.lock');
        Route::post('/hitung-premi/generateBhp/{id}/unlock', [hitungPremiBhpController::class, 'unlock'])->name('backOffice.keuangan.hitungPremi.generateBhp.unlock');
        Route::delete('/hitung-premi/generateBhp/{id}', [hitungPremiBhpController::class, 'destroy'])->name('backOffice.keuangan.hitungPremi.generateBhp.delete');

        Route::get('/hitung-premi/generateKamar', [hitungPremiKamarController::class, 'generateKamar'])->name('backOffice.keuangan.hitungPremi.generateKamar');
        Route::get('/hitung-premi/generateKamar/table', [hitungPremiKamarController::class, 'table'])->name('backOffice.keuangan.hitungPremi.generateKamar.table');
        Route::get('/hitung-premi/generateKamar/summary', [hitungPremiKamarController::class, 'summary'])->name('backOffice.keuangan.hitungPremi.generateKamar.summary');
        Route::get('/hitung-premi/generateKamar/config', [hitungPremiKamarController::class, 'config'])->name('backOffice.keuangan.hitungPremi.generateKamar.config');
        Route::put('/hitung-premi/generateKamar/config', [hitungPremiKamarController::class, 'updateConfig'])->name('backOffice.keuangan.hitungPremi.generateKamar.updateConfig');
        Route::post('/hitung-premi/generateKamar', [hitungPremiKamarController::class, 'store'])->name('backOffice.keuangan.hitungPremi.generateKamar.store');
        Route::post('/hitung-premi/generateKamar/lock-all', [hitungPremiKamarController::class, 'lockAll'])->name('backOffice.keuangan.hitungPremi.generateKamar.lockAll');
        Route::get('/hitung-premi/generateKamar/{id}/detail', [hitungPremiKamarController::class, 'detail'])->name('backOffice.keuangan.hitungPremi.generateKamar.detail');
        Route::post('/hitung-premi/generateKamar/{id}/lock', [hitungPremiKamarController::class, 'lock'])->name('backOffice.keuangan.hitungPremi.generateKamar.lock');
        Route::post('/hitung-premi/generateKamar/{id}/unlock', [hitungPremiKamarController::class, 'unlock'])->name('backOffice.keuangan.hitungPremi.generateKamar.unlock');
        Route::delete('/hitung-premi/generateKamar/{id}', [hitungPremiKamarController::class, 'destroy'])->name('backOffice.keuangan.hitungPremi.generateKamar.delete');

        Route::get('/hitung-premi/generatePelayananNonMedis', [hitungPremiPelayananNonMedisController::class, 'index'])->name('backOffice.keuangan.hitungPremi.generatePelayananNonMedis');
        Route::get('/hitung-premi/generatePelayananNonMedis/table', [hitungPremiPelayananNonMedisController::class, 'table'])->name('backOffice.keuangan.hitungPremi.generatePelayananNonMedis.table');
        Route::get('/hitung-premi/generatePelayananNonMedis/mapping-premi-options', [hitungPremiPelayananNonMedisController::class, 'mappingPremiOptions'])->name('backOffice.keuangan.hitungPremi.generatePelayananNonMedis.mappingPremiOptions');
        Route::get('/hitung-premi/generatePelayananNonMedis/config', [hitungPremiPelayananNonMedisController::class, 'config'])->name('backOffice.keuangan.hitungPremi.generatePelayananNonMedis.config');
        Route::put('/hitung-premi/generatePelayananNonMedis/config', [hitungPremiPelayananNonMedisController::class, 'updateConfig'])->name('backOffice.keuangan.hitungPremi.generatePelayananNonMedis.updateConfig');
        Route::get('/hitung-premi/generatePelayananNonMedis/karcis-config', [hitungPremiPelayananNonMedisController::class, 'karcisConfig'])->name('backOffice.keuangan.hitungPremi.generatePelayananNonMedis.karcisConfig');
        Route::put('/hitung-premi/generatePelayananNonMedis/karcis-config', [hitungPremiPelayananNonMedisController::class, 'updateKarcisConfig'])->name('backOffice.keuangan.hitungPremi.generatePelayananNonMedis.updateKarcisConfig');
        Route::get('/hitung-premi/generatePelayananNonMedis/summary', [hitungPremiPelayananNonMedisController::class, 'summary'])->name('backOffice.keuangan.hitungPremi.generatePelayananNonMedis.summary');
        Route::post('/hitung-premi/generatePelayananNonMedis', [hitungPremiPelayananNonMedisController::class, 'store'])->name('backOffice.keuangan.hitungPremi.generatePelayananNonMedis.store');
        Route::get('/hitung-premi/generatePelayananNonMedis/{id}/detail', [hitungPremiPelayananNonMedisController::class, 'detail'])->name('backOffice.keuangan.hitungPremi.generatePelayananNonMedis.detail');
        Route::post('/hitung-premi/generatePelayananNonMedis/{id}/lock', [hitungPremiPelayananNonMedisController::class, 'lock'])->name('backOffice.keuangan.hitungPremi.generatePelayananNonMedis.lock');
        Route::post('/hitung-premi/generatePelayananNonMedis/{id}/unlock', [hitungPremiPelayananNonMedisController::class, 'unlock'])->name('backOffice.keuangan.hitungPremi.generatePelayananNonMedis.unlock');

        Route::get('/hitung-premi/generateTindakanMedis', [hitungPremiTindakanMedisController::class, 'index'])->name('backOffice.keuangan.hitungPremi.generateTindakanMedis');
        Route::get('/hitung-premi/generateTindakanMedis/table', [hitungPremiTindakanMedisController::class, 'table'])->name('backOffice.keuangan.hitungPremi.generateTindakanMedis.table');
        Route::get('/hitung-premi/generateTindakanMedis/mapping-premi-options', [hitungPremiTindakanMedisController::class, 'mappingPremiOptions'])->name('backOffice.keuangan.hitungPremi.generateTindakanMedis.mappingPremiOptions');
        Route::get('/hitung-premi/generateTindakanMedis/mapping-premi-options/{id}/actions', [hitungPremiTindakanMedisController::class, 'mappingActionOptions'])->name('backOffice.keuangan.hitungPremi.generateTindakanMedis.mappingActionOptions');
        Route::get('/hitung-premi/generateTindakanMedis/dokter-options', [hitungPremiTindakanMedisController::class, 'dokterOptions'])->name('backOffice.keuangan.hitungPremi.generateTindakanMedis.dokterOptions');
        Route::get('/hitung-premi/generateTindakanMedis/config', [hitungPremiTindakanMedisController::class, 'config'])->name('backOffice.keuangan.hitungPremi.generateTindakanMedis.config');
        Route::put('/hitung-premi/generateTindakanMedis/config', [hitungPremiTindakanMedisController::class, 'updateConfig'])->name('backOffice.keuangan.hitungPremi.generateTindakanMedis.updateConfig');
        Route::get('/hitung-premi/generateTindakanMedis/summary', [hitungPremiTindakanMedisController::class, 'summary'])->name('backOffice.keuangan.hitungPremi.generateTindakanMedis.summary');
        Route::post('/hitung-premi/generateTindakanMedis', [hitungPremiTindakanMedisController::class, 'store'])->name('backOffice.keuangan.hitungPremi.generateTindakanMedis.store');
        Route::get('/hitung-premi/generateTindakanMedis/{id}/detail', [hitungPremiTindakanMedisController::class, 'detail'])->name('backOffice.keuangan.hitungPremi.generateTindakanMedis.detail');
        Route::post('/hitung-premi/generateTindakanMedis/{id}/lock', [hitungPremiTindakanMedisController::class, 'lock'])->name('backOffice.keuangan.hitungPremi.generateTindakanMedis.lock');
        Route::post('/hitung-premi/generateTindakanMedis/{id}/unlock', [hitungPremiTindakanMedisController::class, 'unlock'])->name('backOffice.keuangan.hitungPremi.generateTindakanMedis.unlock');

        Route::get('/hitung-premi/generatePremiBersama', [hitungPremiBersamaController::class, 'index'])->name('backOffice.keuangan.hitungPremi.generatePremiBersama');
        Route::get('/hitung-premi/generatePremiBersama/table', [hitungPremiBersamaController::class, 'table'])->name('backOffice.keuangan.hitungPremi.generatePremiBersama.table');
        Route::get('/hitung-premi/generatePremiBersama/mapping-premi-options', [hitungPremiBersamaController::class, 'mappingPremiOptions'])->name('backOffice.keuangan.hitungPremi.generatePremiBersama.mappingPremiOptions');
        Route::get('/hitung-premi/generatePremiBersama/mapping-premi-options/{id}/actions', [hitungPremiBersamaController::class, 'mappingActionOptions'])->name('backOffice.keuangan.hitungPremi.generatePremiBersama.mappingActionOptions');
        Route::get('/hitung-premi/generatePremiBersama/ploting-options', [hitungPremiBersamaController::class, 'plotingOptions'])->name('backOffice.keuangan.hitungPremi.generatePremiBersama.plotingOptions');
        Route::get('/hitung-premi/generatePremiBersama/dokter-options', [hitungPremiBersamaController::class, 'dokterOptions'])->name('backOffice.keuangan.hitungPremi.generatePremiBersama.dokterOptions');
        Route::get('/hitung-premi/generatePremiBersama/config', [hitungPremiBersamaController::class, 'config'])->name('backOffice.keuangan.hitungPremi.generatePremiBersama.config');
        Route::put('/hitung-premi/generatePremiBersama/config', [hitungPremiBersamaController::class, 'updateConfig'])->name('backOffice.keuangan.hitungPremi.generatePremiBersama.updateConfig');
        Route::get('/hitung-premi/generatePremiBersama/summary', [hitungPremiBersamaController::class, 'summary'])->name('backOffice.keuangan.hitungPremi.generatePremiBersama.summary');
        Route::post('/hitung-premi/generatePremiBersama', [hitungPremiBersamaController::class, 'store'])->name('backOffice.keuangan.hitungPremi.generatePremiBersama.store');
        Route::get('/hitung-premi/generatePremiBersama/{id}/detail', [hitungPremiBersamaController::class, 'detail'])->name('backOffice.keuangan.hitungPremi.generatePremiBersama.detail');
        Route::post('/hitung-premi/generatePremiBersama/{id}/lock', [hitungPremiBersamaController::class, 'lock'])->name('backOffice.keuangan.hitungPremi.generatePremiBersama.lock');
        Route::post('/hitung-premi/generatePremiBersama/{id}/unlock', [hitungPremiBersamaController::class, 'unlock'])->name('backOffice.keuangan.hitungPremi.generatePremiBersama.unlock');

        Route::get('/hitung-premi/generatePremiDokter', [hitungPremiDokterController::class, 'index'])->name('backOffice.keuangan.hitungPremi.generatePremiDokter');
        Route::get('/hitung-premi/generatePremiDokter/table', [hitungPremiDokterController::class, 'table'])->name('backOffice.keuangan.hitungPremi.generatePremiDokter.table');
        Route::get('/hitung-premi/generatePremiDokter/summary', [hitungPremiDokterController::class, 'summary'])->name('backOffice.keuangan.hitungPremi.generatePremiDokter.summary');
        Route::get('/hitung-premi/generatePremiDokter/config', [hitungPremiDokterController::class, 'config'])->name('backOffice.keuangan.hitungPremi.generatePremiDokter.config');
        Route::put('/hitung-premi/generatePremiDokter/config', [hitungPremiDokterController::class, 'updateConfig'])->name('backOffice.keuangan.hitungPremi.generatePremiDokter.updateConfig');
        Route::get('/hitung-premi/generatePremiDokter/mapping-tindakan-options', [hitungPremiDokterController::class, 'mappingTindakanOptions'])->name('backOffice.keuangan.hitungPremi.generatePremiDokter.mappingTindakanOptions');
        Route::get('/hitung-premi/generatePremiDokter/dokter-options', [hitungPremiDokterController::class, 'dokterOptions'])->name('backOffice.keuangan.hitungPremi.generatePremiDokter.dokterOptions');
        Route::post('/hitung-premi/generatePremiDokter', [hitungPremiDokterController::class, 'store'])->name('backOffice.keuangan.hitungPremi.generatePremiDokter.store');
        Route::get('/hitung-premi/generatePremiDokter/{id}/detail', [hitungPremiDokterController::class, 'detail'])->name('backOffice.keuangan.hitungPremi.generatePremiDokter.detail');
        Route::post('/hitung-premi/generatePremiDokter/{id}/lock', [hitungPremiDokterController::class, 'lock'])->name('backOffice.keuangan.hitungPremi.generatePremiDokter.lock');
        Route::post('/hitung-premi/generatePremiDokter/{id}/unlock', [hitungPremiDokterController::class, 'unlock'])->name('backOffice.keuangan.hitungPremi.generatePremiDokter.unlock');

        Route::get('/hitung-premi/generateUgd', [hitungPremiUgdController::class, 'index'])->name('backOffice.keuangan.hitungPremi.generateUgd');
        Route::get('/hitung-premi/generateUgd/table', [hitungPremiUgdController::class, 'table'])->name('backOffice.keuangan.hitungPremi.generateUgd.table');
        Route::get('/hitung-premi/generateUgd/summary', [hitungPremiUgdController::class, 'summary'])->name('backOffice.keuangan.hitungPremi.generateUgd.summary');
        Route::get('/hitung-premi/generateUgd/dokter-options', [hitungPremiUgdController::class, 'dokterOptions'])->name('backOffice.keuangan.hitungPremi.generateUgd.dokterOptions');
        Route::get('/hitung-premi/generateUgd/ploting-options', [hitungPremiUgdController::class, 'plotingOptions'])->name('backOffice.keuangan.hitungPremi.generateUgd.plotingOptions');
        Route::get('/hitung-premi/generateUgd/config', [hitungPremiUgdController::class, 'config'])->name('backOffice.keuangan.hitungPremi.generateUgd.config');
        Route::put('/hitung-premi/generateUgd/config', [hitungPremiUgdController::class, 'updateConfig'])->name('backOffice.keuangan.hitungPremi.generateUgd.updateConfig');
        Route::get('/hitung-premi/generateUgd/copy-preview', [hitungPremiUgdController::class, 'copyPreview'])->name('backOffice.keuangan.hitungPremi.generateUgd.copyPreview');
        Route::post('/hitung-premi/generateUgd', [hitungPremiUgdController::class, 'store'])->name('backOffice.keuangan.hitungPremi.generateUgd.store');
        Route::post('/hitung-premi/generateUgd/lock-all', [hitungPremiUgdController::class, 'lockAll'])->name('backOffice.keuangan.hitungPremi.generateUgd.lockAll');
        Route::post('/hitung-premi/generateUgd/{id}/lock', [hitungPremiUgdController::class, 'lock'])->name('backOffice.keuangan.hitungPremi.generateUgd.lock');
        Route::post('/hitung-premi/generateUgd/{id}/unlock', [hitungPremiUgdController::class, 'unlock'])->name('backOffice.keuangan.hitungPremi.generateUgd.unlock');
        Route::delete('/hitung-premi/generateUgd/{id}/delete', [hitungPremiUgdController::class, 'destroy'])->name('backOffice.keuangan.hitungPremi.generateUgd.delete');

        Route::get('/hitung-premi/generateVk', [hitungPremiVkController::class, 'index'])->name('backOffice.keuangan.hitungPremi.generateVk');
        Route::get('/hitung-premi/generateVk/table', [hitungPremiVkController::class, 'table'])->name('backOffice.keuangan.hitungPremi.generateVk.table');
        Route::get('/hitung-premi/generateVk/summary', [hitungPremiVkController::class, 'summary'])->name('backOffice.keuangan.hitungPremi.generateVk.summary');
        Route::get('/hitung-premi/generateVk/tindakan-options', [hitungPremiVkController::class, 'tindakanOptions'])->name('backOffice.keuangan.hitungPremi.generateVk.tindakanOptions');
        Route::get('/hitung-premi/generateVk/ploting-options', [hitungPremiVkController::class, 'plotingOptions'])->name('backOffice.keuangan.hitungPremi.generateVk.plotingOptions');
        Route::get('/hitung-premi/generateVk/config', [hitungPremiVkController::class, 'config'])->name('backOffice.keuangan.hitungPremi.generateVk.config');
        Route::put('/hitung-premi/generateVk/config', [hitungPremiVkController::class, 'updateConfig'])->name('backOffice.keuangan.hitungPremi.generateVk.updateConfig');
        Route::get('/hitung-premi/generateVk/pegawai-options', [hitungPremiVkController::class, 'pegawaiOptions'])->name('backOffice.keuangan.hitungPremi.generateVk.pegawaiOptions');
        Route::get('/hitung-premi/generateVk/copy-preview', [hitungPremiVkController::class, 'copyPreview'])->name('backOffice.keuangan.hitungPremi.generateVk.copyPreview');
        Route::post('/hitung-premi/generateVk', [hitungPremiVkController::class, 'store'])->name('backOffice.keuangan.hitungPremi.generateVk.store');
        Route::post('/hitung-premi/generateVk/lock-all', [hitungPremiVkController::class, 'lockAll'])->name('backOffice.keuangan.hitungPremi.generateVk.lockAll');
        Route::post('/hitung-premi/generateVk/{id}/lock', [hitungPremiVkController::class, 'lock'])->name('backOffice.keuangan.hitungPremi.generateVk.lock');
        Route::post('/hitung-premi/generateVk/{id}/unlock', [hitungPremiVkController::class, 'unlock'])->name('backOffice.keuangan.hitungPremi.generateVk.unlock');
        Route::delete('/hitung-premi/generateVk/{id}/delete', [hitungPremiVkController::class, 'destroy'])->name('backOffice.keuangan.hitungPremi.generateVk.delete');

        Route::get('/hitung-premi/generatePremiDriver', [hitungPremiDriverController::class, 'index'])->name('backOffice.keuangan.hitungPremi.generatePremiDriver');
        Route::get('/hitung-premi/generatePremiDriver/table', [hitungPremiDriverController::class, 'table'])->name('backOffice.keuangan.hitungPremi.generatePremiDriver.table');
        Route::get('/hitung-premi/generatePremiDriver/summary', [hitungPremiDriverController::class, 'summary'])->name('backOffice.keuangan.hitungPremi.generatePremiDriver.summary');
        Route::get('/hitung-premi/generatePremiDriver/config', [hitungPremiDriverController::class, 'config'])->name('backOffice.keuangan.hitungPremi.generatePremiDriver.config');
        Route::put('/hitung-premi/generatePremiDriver/config', [hitungPremiDriverController::class, 'updateConfig'])->name('backOffice.keuangan.hitungPremi.generatePremiDriver.updateConfig');
        Route::get('/hitung-premi/generatePremiDriver/tujuan', [hitungPremiDriverController::class, 'tujuanList'])->name('backOffice.keuangan.hitungPremi.generatePremiDriver.tujuanList');
        Route::post('/hitung-premi/generatePremiDriver/tujuan', [hitungPremiDriverController::class, 'storeTujuan'])->name('backOffice.keuangan.hitungPremi.generatePremiDriver.storeTujuan');
        Route::get('/hitung-premi/generatePremiDriver/tujuan-options', [hitungPremiDriverController::class, 'tujuanOptions'])->name('backOffice.keuangan.hitungPremi.generatePremiDriver.tujuanOptions');
        Route::put('/hitung-premi/generatePremiDriver/tujuan/{id}', [hitungPremiDriverController::class, 'updateTujuan'])->name('backOffice.keuangan.hitungPremi.generatePremiDriver.updateTujuan');
        Route::delete('/hitung-premi/generatePremiDriver/tujuan/{id}', [hitungPremiDriverController::class, 'deleteTujuan'])->name('backOffice.keuangan.hitungPremi.generatePremiDriver.deleteTujuan');
        Route::get('/hitung-premi/generatePremiDriver/pegawai-options', [hitungPremiDriverController::class, 'pegawaiOptions'])->name('backOffice.keuangan.hitungPremi.generatePremiDriver.pegawaiOptions');
        Route::post('/hitung-premi/generatePremiDriver/preview', [hitungPremiDriverController::class, 'preview'])->name('backOffice.keuangan.hitungPremi.generatePremiDriver.preview');
        Route::post('/hitung-premi/generatePremiDriver', [hitungPremiDriverController::class, 'store'])->name('backOffice.keuangan.hitungPremi.generatePremiDriver.store');
        Route::get('/hitung-premi/generatePremiDriver/{id}/detail', [hitungPremiDriverController::class, 'detail'])->name('backOffice.keuangan.hitungPremi.generatePremiDriver.detail');
        Route::post('/hitung-premi/generatePremiDriver/{id}/lock', [hitungPremiDriverController::class, 'lock'])->name('backOffice.keuangan.hitungPremi.generatePremiDriver.lock');
        Route::post('/hitung-premi/generatePremiDriver/{id}/unlock', [hitungPremiDriverController::class, 'unlock'])->name('backOffice.keuangan.hitungPremi.generatePremiDriver.unlock');
        Route::delete('/hitung-premi/generatePremiDriver/{id}/delete', [hitungPremiDriverController::class, 'destroy'])->name('backOffice.keuangan.hitungPremi.generatePremiDriver.delete');

        Route::get('/hitung-premi/generateOperasi', [hitungPremiOperasiController::class, 'index'])->name('backOffice.keuangan.hitungPremi.generateOperasi');
        Route::get('/hitung-premi/generateOperasi/table', [hitungPremiOperasiController::class, 'table'])->name('backOffice.keuangan.hitungPremi.generateOperasi.table');
        Route::get('/hitung-premi/generateOperasi/summary', [hitungPremiOperasiController::class, 'summary'])->name('backOffice.keuangan.hitungPremi.generateOperasi.summary');
        Route::get('/hitung-premi/generateOperasi/config', [hitungPremiOperasiController::class, 'config'])->name('backOffice.keuangan.hitungPremi.generateOperasi.config');
        Route::put('/hitung-premi/generateOperasi/config', [hitungPremiOperasiController::class, 'updateConfig'])->name('backOffice.keuangan.hitungPremi.generateOperasi.updateConfig');
        Route::get('/hitung-premi/generateOperasi/pegawai-options', [hitungPremiOperasiController::class, 'pegawaiOptions'])->name('backOffice.keuangan.hitungPremi.generateOperasi.pegawaiOptions');
        Route::get('/hitung-premi/generateOperasi/dokter-options', [hitungPremiOperasiController::class, 'dokterOptions'])->name('backOffice.keuangan.hitungPremi.generateOperasi.dokterOptions');
        Route::get('/hitung-premi/generateOperasi/preview', [hitungPremiOperasiController::class, 'preview'])->name('backOffice.keuangan.hitungPremi.generateOperasi.preview');
        Route::post('/hitung-premi/generateOperasi', [hitungPremiOperasiController::class, 'store'])->name('backOffice.keuangan.hitungPremi.generateOperasi.store');
        Route::get('/hitung-premi/generateOperasi/{id}/detail', [hitungPremiOperasiController::class, 'detail'])->name('backOffice.keuangan.hitungPremi.generateOperasi.detail');
        Route::post('/hitung-premi/generateOperasi/{id}/lock', [hitungPremiOperasiController::class, 'lock'])->name('backOffice.keuangan.hitungPremi.generateOperasi.lock');
        Route::post('/hitung-premi/generateOperasi/{id}/unlock', [hitungPremiOperasiController::class, 'unlock'])->name('backOffice.keuangan.hitungPremi.generateOperasi.unlock');
        Route::delete('/hitung-premi/generateOperasi/{id}/delete', [hitungPremiOperasiController::class, 'destroy'])->name('backOffice.keuangan.hitungPremi.generateOperasi.delete');

        Route::get('/hitung-premi/generateRadiologi', [hitungPremiRadiologiController::class, 'index'])->name('backOffice.keuangan.hitungPremi.generateRadiologi');
        Route::get('/hitung-premi/generateRadiologi/table', [hitungPremiRadiologiController::class, 'table'])->name('backOffice.keuangan.hitungPremi.generateRadiologi.table');
        Route::get('/hitung-premi/generateRadiologi/summary', [hitungPremiRadiologiController::class, 'summary'])->name('backOffice.keuangan.hitungPremi.generateRadiologi.summary');
        Route::get('/hitung-premi/generateRadiologi/config', [hitungPremiRadiologiController::class, 'config'])->name('backOffice.keuangan.hitungPremi.generateRadiologi.config');
        Route::put('/hitung-premi/generateRadiologi/config', [hitungPremiRadiologiController::class, 'updateConfig'])->name('backOffice.keuangan.hitungPremi.generateRadiologi.updateConfig');
        Route::get('/hitung-premi/generateRadiologi/pegawai-options', [hitungPremiRadiologiController::class, 'pegawaiOptions'])->name('backOffice.keuangan.hitungPremi.generateRadiologi.pegawaiOptions');
        Route::get('/hitung-premi/generateRadiologi/preview', [hitungPremiRadiologiController::class, 'preview'])->name('backOffice.keuangan.hitungPremi.generateRadiologi.preview');
        Route::post('/hitung-premi/generateRadiologi', [hitungPremiRadiologiController::class, 'store'])->name('backOffice.keuangan.hitungPremi.generateRadiologi.store');
        Route::get('/hitung-premi/generateRadiologi/{id}/detail', [hitungPremiRadiologiController::class, 'detail'])->name('backOffice.keuangan.hitungPremi.generateRadiologi.detail');
        Route::post('/hitung-premi/generateRadiologi/{id}/lock', [hitungPremiRadiologiController::class, 'lock'])->name('backOffice.keuangan.hitungPremi.generateRadiologi.lock');
        Route::post('/hitung-premi/generateRadiologi/{id}/unlock', [hitungPremiRadiologiController::class, 'unlock'])->name('backOffice.keuangan.hitungPremi.generateRadiologi.unlock');
        Route::delete('/hitung-premi/generateRadiologi/{id}/delete', [hitungPremiRadiologiController::class, 'destroy'])->name('backOffice.keuangan.hitungPremi.generateRadiologi.delete');

        Route::get('/hitung-premi/generateLaboratorium', [hitungPremiLaboratoriumController::class, 'index'])->name('backOffice.keuangan.hitungPremi.generateLaboratorium');
        Route::get('/hitung-premi/generateLaboratorium/table', [hitungPremiLaboratoriumController::class, 'table'])->name('backOffice.keuangan.hitungPremi.generateLaboratorium.table');
        Route::get('/hitung-premi/generateLaboratorium/summary', [hitungPremiLaboratoriumController::class, 'summary'])->name('backOffice.keuangan.hitungPremi.generateLaboratorium.summary');
        Route::get('/hitung-premi/generateLaboratorium/config', [hitungPremiLaboratoriumController::class, 'config'])->name('backOffice.keuangan.hitungPremi.generateLaboratorium.config');
        Route::put('/hitung-premi/generateLaboratorium/config', [hitungPremiLaboratoriumController::class, 'updateConfig'])->name('backOffice.keuangan.hitungPremi.generateLaboratorium.updateConfig');
        Route::get('/hitung-premi/generateLaboratorium/pegawai-options', [hitungPremiLaboratoriumController::class, 'pegawaiOptions'])->name('backOffice.keuangan.hitungPremi.generateLaboratorium.pegawaiOptions');
        Route::get('/hitung-premi/generateLaboratorium/preview', [hitungPremiLaboratoriumController::class, 'preview'])->name('backOffice.keuangan.hitungPremi.generateLaboratorium.preview');
        Route::post('/hitung-premi/generateLaboratorium', [hitungPremiLaboratoriumController::class, 'store'])->name('backOffice.keuangan.hitungPremi.generateLaboratorium.store');
        Route::get('/hitung-premi/generateLaboratorium/{id}/detail', [hitungPremiLaboratoriumController::class, 'detail'])->name('backOffice.keuangan.hitungPremi.generateLaboratorium.detail');
        Route::post('/hitung-premi/generateLaboratorium/{id}/lock', [hitungPremiLaboratoriumController::class, 'lock'])->name('backOffice.keuangan.hitungPremi.generateLaboratorium.lock');
        Route::post('/hitung-premi/generateLaboratorium/{id}/unlock', [hitungPremiLaboratoriumController::class, 'unlock'])->name('backOffice.keuangan.hitungPremi.generateLaboratorium.unlock');
        Route::delete('/hitung-premi/generateLaboratorium/{id}/delete', [hitungPremiLaboratoriumController::class, 'destroy'])->name('backOffice.keuangan.hitungPremi.generateLaboratorium.delete');

        Route::get('/hitung-premi/generateFisio', [hitungPremiFisioController::class, 'index'])->name('backOffice.keuangan.hitungPremi.generateFisio');
        Route::get('/hitung-premi/generateFisio/table', [hitungPremiFisioController::class, 'table'])->name('backOffice.keuangan.hitungPremi.generateFisio.table');
        Route::get('/hitung-premi/generateFisio/summary', [hitungPremiFisioController::class, 'summary'])->name('backOffice.keuangan.hitungPremi.generateFisio.summary');
        Route::get('/hitung-premi/generateFisio/config', [hitungPremiFisioController::class, 'config'])->name('backOffice.keuangan.hitungPremi.generateFisio.config');
        Route::put('/hitung-premi/generateFisio/config', [hitungPremiFisioController::class, 'updateConfig'])->name('backOffice.keuangan.hitungPremi.generateFisio.updateConfig');
        Route::get('/hitung-premi/generateFisio/pegawai-options', [hitungPremiFisioController::class, 'pegawaiOptions'])->name('backOffice.keuangan.hitungPremi.generateFisio.pegawaiOptions');
        Route::get('/hitung-premi/generateFisio/tindakan-options', [hitungPremiFisioController::class, 'tindakanOptions'])->name('backOffice.keuangan.hitungPremi.generateFisio.tindakanOptions');
        Route::get('/hitung-premi/generateFisio/preview', [hitungPremiFisioController::class, 'preview'])->name('backOffice.keuangan.hitungPremi.generateFisio.preview');
        Route::post('/hitung-premi/generateFisio', [hitungPremiFisioController::class, 'store'])->name('backOffice.keuangan.hitungPremi.generateFisio.store');
        Route::get('/hitung-premi/generateFisio/{id}/detail', [hitungPremiFisioController::class, 'detail'])->name('backOffice.keuangan.hitungPremi.generateFisio.detail');
        Route::post('/hitung-premi/generateFisio/{id}/lock', [hitungPremiFisioController::class, 'lock'])->name('backOffice.keuangan.hitungPremi.generateFisio.lock');
        Route::post('/hitung-premi/generateFisio/{id}/unlock', [hitungPremiFisioController::class, 'unlock'])->name('backOffice.keuangan.hitungPremi.generateFisio.unlock');
        Route::delete('/hitung-premi/generateFisio/{id}/delete', [hitungPremiFisioController::class, 'destroy'])->name('backOffice.keuangan.hitungPremi.generateFisio.delete');

        Route::get('/hitung-premi/generateIcu', [hitungPremiIcuController::class, 'index'])->name('backOffice.keuangan.hitungPremi.generateIcu');
        Route::get('/hitung-premi/generateIcu/table', [hitungPremiIcuController::class, 'table'])->name('backOffice.keuangan.hitungPremi.generateIcu.table');
        Route::get('/hitung-premi/generateIcu/summary', [hitungPremiIcuController::class, 'summary'])->name('backOffice.keuangan.hitungPremi.generateIcu.summary');
        Route::get('/hitung-premi/generateIcu/config', [hitungPremiIcuController::class, 'config'])->name('backOffice.keuangan.hitungPremi.generateIcu.config');
        Route::put('/hitung-premi/generateIcu/config', [hitungPremiIcuController::class, 'updateConfig'])->name('backOffice.keuangan.hitungPremi.generateIcu.updateConfig');
        Route::get('/hitung-premi/generateIcu/mapping-options', [hitungPremiIcuController::class, 'mappingOptions'])->name('backOffice.keuangan.hitungPremi.generateIcu.mappingOptions');
        Route::get('/hitung-premi/generateIcu/critical-action-options', [hitungPremiIcuController::class, 'criticalActionOptions'])->name('backOffice.keuangan.hitungPremi.generateIcu.criticalActionOptions');
        Route::get('/hitung-premi/generateIcu/pegawai-options', [hitungPremiIcuController::class, 'pegawaiOptions'])->name('backOffice.keuangan.hitungPremi.generateIcu.pegawaiOptions');
        Route::get('/hitung-premi/generateIcu/preview', [hitungPremiIcuController::class, 'preview'])->name('backOffice.keuangan.hitungPremi.generateIcu.preview');
        Route::post('/hitung-premi/generateIcu', [hitungPremiIcuController::class, 'store'])->name('backOffice.keuangan.hitungPremi.generateIcu.store');
        Route::get('/hitung-premi/generateIcu/{id}/detail', [hitungPremiIcuController::class, 'detail'])->name('backOffice.keuangan.hitungPremi.generateIcu.detail');
        Route::post('/hitung-premi/generateIcu/{id}/lock', [hitungPremiIcuController::class, 'lock'])->name('backOffice.keuangan.hitungPremi.generateIcu.lock');
        Route::post('/hitung-premi/generateIcu/{id}/unlock', [hitungPremiIcuController::class, 'unlock'])->name('backOffice.keuangan.hitungPremi.generateIcu.unlock');
        Route::delete('/hitung-premi/generateIcu/{id}/delete', [hitungPremiIcuController::class, 'destroy'])->name('backOffice.keuangan.hitungPremi.generateIcu.delete');

        Route::get('/hitung-premi/generateGizi', [hitungPremiGiziController::class, 'index'])->name('backOffice.keuangan.hitungPremi.generateGizi');
        Route::get('/hitung-premi/generateGizi/table', [hitungPremiGiziController::class, 'table'])->name('backOffice.keuangan.hitungPremi.generateGizi.table');
        Route::get('/hitung-premi/generateGizi/summary', [hitungPremiGiziController::class, 'summary'])->name('backOffice.keuangan.hitungPremi.generateGizi.summary');
        Route::get('/hitung-premi/generateGizi/config', [hitungPremiGiziController::class, 'config'])->name('backOffice.keuangan.hitungPremi.generateGizi.config');
        Route::put('/hitung-premi/generateGizi/config', [hitungPremiGiziController::class, 'updateConfig'])->name('backOffice.keuangan.hitungPremi.generateGizi.updateConfig');
        Route::get('/hitung-premi/generateGizi/mapping-options', [hitungPremiGiziController::class, 'mappingOptions'])->name('backOffice.keuangan.hitungPremi.generateGizi.mappingOptions');
        Route::get('/hitung-premi/generateGizi/pegawai-options', [hitungPremiGiziController::class, 'pegawaiOptions'])->name('backOffice.keuangan.hitungPremi.generateGizi.pegawaiOptions');
        Route::get('/hitung-premi/generateGizi/preview', [hitungPremiGiziController::class, 'preview'])->name('backOffice.keuangan.hitungPremi.generateGizi.preview');
        Route::post('/hitung-premi/generateGizi', [hitungPremiGiziController::class, 'store'])->name('backOffice.keuangan.hitungPremi.generateGizi.store');
        Route::get('/hitung-premi/generateGizi/{id}/detail', [hitungPremiGiziController::class, 'detail'])->name('backOffice.keuangan.hitungPremi.generateGizi.detail');
        Route::post('/hitung-premi/generateGizi/{id}/lock', [hitungPremiGiziController::class, 'lock'])->name('backOffice.keuangan.hitungPremi.generateGizi.lock');
        Route::post('/hitung-premi/generateGizi/{id}/unlock', [hitungPremiGiziController::class, 'unlock'])->name('backOffice.keuangan.hitungPremi.generateGizi.unlock');
        Route::delete('/hitung-premi/generateGizi/{id}/delete', [hitungPremiGiziController::class, 'destroy'])->name('backOffice.keuangan.hitungPremi.generateGizi.delete');

        Route::get('/hitung-premi/generateNicu', [hitungPremiNicuController::class, 'index'])->name('backOffice.keuangan.hitungPremi.generateNicu');
        Route::get('/hitung-premi/generateNicu/table', [hitungPremiNicuController::class, 'table'])->name('backOffice.keuangan.hitungPremi.generateNicu.table');
        Route::get('/hitung-premi/generateNicu/summary', [hitungPremiNicuController::class, 'summary'])->name('backOffice.keuangan.hitungPremi.generateNicu.summary');
        Route::get('/hitung-premi/generateNicu/config', [hitungPremiNicuController::class, 'config'])->name('backOffice.keuangan.hitungPremi.generateNicu.config');
        Route::put('/hitung-premi/generateNicu/config', [hitungPremiNicuController::class, 'updateConfig'])->name('backOffice.keuangan.hitungPremi.generateNicu.updateConfig');
        Route::get('/hitung-premi/generateNicu/mapping-options', [hitungPremiNicuController::class, 'mappingOptions'])->name('backOffice.keuangan.hitungPremi.generateNicu.mappingOptions');
        Route::get('/hitung-premi/generateNicu/critical-action-options', [hitungPremiNicuController::class, 'criticalActionOptions'])->name('backOffice.keuangan.hitungPremi.generateNicu.criticalActionOptions');
        Route::get('/hitung-premi/generateNicu/pegawai-options', [hitungPremiNicuController::class, 'pegawaiOptions'])->name('backOffice.keuangan.hitungPremi.generateNicu.pegawaiOptions');
        Route::get('/hitung-premi/generateNicu/preview', [hitungPremiNicuController::class, 'preview'])->name('backOffice.keuangan.hitungPremi.generateNicu.preview');
        Route::post('/hitung-premi/generateNicu', [hitungPremiNicuController::class, 'store'])->name('backOffice.keuangan.hitungPremi.generateNicu.store');
        Route::get('/hitung-premi/generateNicu/{id}/detail', [hitungPremiNicuController::class, 'detail'])->name('backOffice.keuangan.hitungPremi.generateNicu.detail');
        Route::post('/hitung-premi/generateNicu/{id}/lock', [hitungPremiNicuController::class, 'lock'])->name('backOffice.keuangan.hitungPremi.generateNicu.lock');
        Route::post('/hitung-premi/generateNicu/{id}/unlock', [hitungPremiNicuController::class, 'unlock'])->name('backOffice.keuangan.hitungPremi.generateNicu.unlock');
        Route::delete('/hitung-premi/generateNicu/{id}/delete', [hitungPremiNicuController::class, 'destroy'])->name('backOffice.keuangan.hitungPremi.generateNicu.delete');

        Route::get('/hitung-premi/generateApotek', [hitungPremiApotekController::class, 'index'])->name('backOffice.keuangan.hitungPremi.generateApotek');
        Route::get('/hitung-premi/generateApotek/table', [hitungPremiApotekController::class, 'table'])->name('backOffice.keuangan.hitungPremi.generateApotek.table');
        Route::get('/hitung-premi/generateApotek/summary', [hitungPremiApotekController::class, 'summary'])->name('backOffice.keuangan.hitungPremi.generateApotek.summary');
        Route::get('/hitung-premi/generateApotek/config', [hitungPremiApotekController::class, 'config'])->name('backOffice.keuangan.hitungPremi.generateApotek.config');
        Route::put('/hitung-premi/generateApotek/config', [hitungPremiApotekController::class, 'updateConfig'])->name('backOffice.keuangan.hitungPremi.generateApotek.updateConfig');
        Route::get('/hitung-premi/generateApotek/mapping-options', [hitungPremiApotekController::class, 'mappingOptions'])->name('backOffice.keuangan.hitungPremi.generateApotek.mappingOptions');
        Route::get('/hitung-premi/generateApotek/pegawai-options', [hitungPremiApotekController::class, 'pegawaiOptions'])->name('backOffice.keuangan.hitungPremi.generateApotek.pegawaiOptions');
        Route::get('/hitung-premi/generateApotek/preview', [hitungPremiApotekController::class, 'preview'])->name('backOffice.keuangan.hitungPremi.generateApotek.preview');
        Route::post('/hitung-premi/generateApotek', [hitungPremiApotekController::class, 'store'])->name('backOffice.keuangan.hitungPremi.generateApotek.store');
        Route::get('/hitung-premi/generateApotek/{id}/detail', [hitungPremiApotekController::class, 'detail'])->name('backOffice.keuangan.hitungPremi.generateApotek.detail');
        Route::post('/hitung-premi/generateApotek/{id}/lock', [hitungPremiApotekController::class, 'lock'])->name('backOffice.keuangan.hitungPremi.generateApotek.lock');
        Route::post('/hitung-premi/generateApotek/{id}/unlock', [hitungPremiApotekController::class, 'unlock'])->name('backOffice.keuangan.hitungPremi.generateApotek.unlock');
        Route::delete('/hitung-premi/generateApotek/{id}/delete', [hitungPremiApotekController::class, 'destroy'])->name('backOffice.keuangan.hitungPremi.generateApotek.delete');

        Route::get('/hitung-premi/generateCasemix', [hitungPremiCasemixController::class, 'index'])->name('backOffice.keuangan.hitungPremi.generateCasemix');
        Route::get('/hitung-premi/generateCasemix/table', [hitungPremiCasemixController::class, 'table'])->name('backOffice.keuangan.hitungPremi.generateCasemix.table');
        Route::get('/hitung-premi/generateCasemix/summary', [hitungPremiCasemixController::class, 'summary'])->name('backOffice.keuangan.hitungPremi.generateCasemix.summary');
        Route::get('/hitung-premi/generateCasemix/config', [hitungPremiCasemixController::class, 'config'])->name('backOffice.keuangan.hitungPremi.generateCasemix.config');
        Route::put('/hitung-premi/generateCasemix/config', [hitungPremiCasemixController::class, 'updateConfig'])->name('backOffice.keuangan.hitungPremi.generateCasemix.updateConfig');
        Route::get('/hitung-premi/generateCasemix/pegawai-options', [hitungPremiCasemixController::class, 'pegawaiOptions'])->name('backOffice.keuangan.hitungPremi.generateCasemix.pegawaiOptions');
        Route::get('/hitung-premi/generateCasemix/preview', [hitungPremiCasemixController::class, 'preview'])->name('backOffice.keuangan.hitungPremi.generateCasemix.preview');
        Route::post('/hitung-premi/generateCasemix', [hitungPremiCasemixController::class, 'store'])->name('backOffice.keuangan.hitungPremi.generateCasemix.store');
        Route::get('/hitung-premi/generateCasemix/{id}/detail', [hitungPremiCasemixController::class, 'detail'])->name('backOffice.keuangan.hitungPremi.generateCasemix.detail');
        Route::post('/hitung-premi/generateCasemix/{id}/lock', [hitungPremiCasemixController::class, 'lock'])->name('backOffice.keuangan.hitungPremi.generateCasemix.lock');
        Route::post('/hitung-premi/generateCasemix/{id}/unlock', [hitungPremiCasemixController::class, 'unlock'])->name('backOffice.keuangan.hitungPremi.generateCasemix.unlock');
        Route::delete('/hitung-premi/generateCasemix/{id}/delete', [hitungPremiCasemixController::class, 'destroy'])->name('backOffice.keuangan.hitungPremi.generateCasemix.delete');

        Route::get('/penggajian', [penggajianController::class, 'index'])->name('backOffice.keuangan.penggajian');
        Route::get('/penggajian/getGajiTahap1Table', [penggajianController::class, 'getGajiTahap1Table'])->name('backOffice.keuangan.penggajian.getGajiTahap1Table');
        Route::get('/penggajian/getGajiTahap2Table', [penggajianController::class, 'getGajiTahap2Table'])->name('backOffice.keuangan.penggajian.getGajiTahap2Table');
        Route::get('/penggajian/getPenggajianDetail', [penggajianController::class, 'getPenggajianDetail'])->name('backOffice.keuangan.penggajian.getPenggajianDetail');
        Route::post('/penggajian/generateGajiTahap1', [penggajianController::class, 'generateGajiTahap1'])->name('backOffice.keuangan.penggajian.generateGajiTahap1');
        Route::post('/penggajian/generateGajiTahap2', [penggajianController::class, 'generateGajiTahap2'])->name('backOffice.keuangan.penggajian.generateGajiTahap2');
        Route::get('/penggajian/getSummaryGajiTahap1', [penggajianController::class, 'getSummaryGajiTahap1'])->name('backOffice.keuangan.penggajian.getSummaryGajiTahap1');
        Route::get('/penggajian/getSummaryGajiTahap2', [penggajianController::class, 'getSummaryGajiTahap2'])->name('backOffice.keuangan.penggajian.getSummaryGajiTahap2');
        Route::get('/penggajian/gajitahap2/generator-readiness', [penggajianController::class, 'getGajiTahap2GeneratorReadiness'])->name('backOffice.keuangan.penggajian.getGajiTahap2GeneratorReadiness');
        Route::get('/penggajian/gajitahap1/doctor-config', [penggajianController::class, 'gajiTahap1DoctorConfig'])->name('backOffice.keuangan.penggajian.gajiTahap1DoctorConfig');
        Route::put('/penggajian/gajitahap1/doctor-config', [penggajianController::class, 'updateGajiTahap1DoctorConfig'])->name('backOffice.keuangan.penggajian.updateGajiTahap1DoctorConfig');
        Route::get('/penggajian/gajitahap1/dokter-ugd-kontrak-options', [penggajianController::class, 'dokterUgdKontrakTahap1Options'])->name('backOffice.keuangan.penggajian.dokterUgdKontrakTahap1Options');
        Route::get('/penggajian/gajitahap2/doctor-config', [penggajianController::class, 'gajiTahap2DoctorConfig'])->name('backOffice.keuangan.penggajian.gajiTahap2DoctorConfig');
        Route::put('/penggajian/gajitahap2/doctor-config', [penggajianController::class, 'updateGajiTahap2DoctorConfig'])->name('backOffice.keuangan.penggajian.updateGajiTahap2DoctorConfig');
        Route::get('/penggajian/gajitahap2/dokter-umum-options', [penggajianController::class, 'dokterUmumTahap2Options'])->name('backOffice.keuangan.penggajian.dokterUmumTahap2Options');
        Route::get('/penggajian/rounding-config', [penggajianController::class, 'payrollRoundingConfig'])->name('backOffice.keuangan.penggajian.payrollRoundingConfig');
        Route::put('/penggajian/rounding-config', [penggajianController::class, 'updatePayrollRoundingConfig'])->name('backOffice.keuangan.penggajian.updatePayrollRoundingConfig');
        Route::get('/penggajian/slip/recipients', [penggajianController::class, 'getPenerimaSlip'])->name('backOffice.keuangan.penggajian.getPenerimaSlip');
        Route::post('/penggajian/slip/send', [penggajianController::class, 'kirimSlipGaji'])->name('backOffice.keuangan.penggajian.kirimSlipGaji');
        Route::get('/penggajian/slip-whatsapp/recipients', [penggajianController::class, 'getPenerimaSlipWhatsappTahap1'])->name('backOffice.keuangan.penggajian.getPenerimaSlipWhatsappTahap1');
        Route::post('/penggajian/slip-whatsapp/send', [penggajianController::class, 'kirimSlipGajiWhatsappTahap1'])->name('backOffice.keuangan.penggajian.kirimSlipGajiWhatsappTahap1');
        Route::get('/penggajian/gajitahap1/{id}/detail', [penggajianController::class, 'detailGajiTahap1'])->name('backOffice.keuangan.penggajian.detailGajiTahap1');
        Route::get('/penggajian/gajitahap1/{id}/export-pdf', [penggajianController::class, 'exportSlipGajiTahap1Pdf'])->name('backOffice.keuangan.penggajian.exportSlipGajiTahap1Pdf');
        Route::get('/penggajian/gajitahap2/{id}/detail', [penggajianController::class, 'detailGajiTahap2'])->name('backOffice.keuangan.penggajian.detailGajiTahap2');
        Route::get('/penggajian/gajitahap2/{id}/export-pdf', [penggajianController::class, 'exportSlipGajiTahap2Pdf'])->name('backOffice.keuangan.penggajian.exportSlipGajiTahap2Pdf');
        Route::get('/penggajian/export-excel', [penggajianController::class, 'exportGajiExcel'])->name('backOffice.keuangan.penggajian.exportGajiExcel');
        Route::get('/penggajian/gajitahap2/export-excel', [penggajianController::class, 'exportGajiTahap2Excel'])->name('backOffice.keuangan.penggajian.exportGajiTahap2Excel');

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
        Route::put('/tunjangan/update-inline/{id}', [TunjanganPegawaiController::class, 'updateInline'])->name('masterData.keuangan.tunjanganPegawai.updateInline');
        Route::put('/tunjangan/bulk-update', [TunjanganPegawaiController::class, 'bulkUpdate'])->name('masterData.keuangan.tunjanganPegawai.bulkUpdate');
        Route::delete('/tunjanganPegawai/{id}/delete', [tunjanganPegawaiController::class, 'destroy'])->name('masterData.keuangan.tunjanganPegawai.delete');
        Route::get('/tunjangan/by-pegawai/{nik}', [TunjanganPegawaiController::class, 'getByPegawai'])->name('masterData.keuangan.tunjanganPegawai.getByPegawai');
        Route::post('/tunjangan/distribusi', [TunjanganPegawaiController::class, 'distribusi'])->name('masterData.keuangan.tunjangan.distribusi');
        Route::post('/tunjangan/preview-distribusi', [TunjanganPegawaiController::class, 'previewDistribusi'])->name('masterData.keuangan.tunjangan.previewDistribusi');
        Route::get('/tunjanganPegawai/getJabatan', [tunjanganPegawaiController::class, 'getJabatan'])->name('masterData.keuangan.tunjanganPegawai.getJabatan');
        Route::get('/tunjanganPegawai/getProfesi', [tunjanganPegawaiController::class, 'getProfesi'])->name('masterData.keuangan.tunjanganPegawai.getProfesi');
        Route::get('/tunjanganPegawai/getGapokById/{nik}', [tunjanganPegawaiController::class, 'getGapokById'])->name('masterData.keuangan.tunjanganPegawai.getGapokById');

        Route::get('/potongan', [jenisPotonganController::class, 'index'])->name('masterData.keuangan.potongan');
        Route::get('/potongan/generateKode', [jenisPotonganController::class, 'generateKodePotongan'])->name('masterData.keuangan.potongan.generateKode');
        Route::get('/potongan/getPotonganTable', [jenisPotonganController::class, 'getJnsPotonganTable'])->name('masterData.keuangan.potongan.getJnsPotonganTable');
        Route::post('/potongan/store', [jenisPotonganController::class, 'store'])->name('masterData.keuangan.potongan.store');
        Route::get('/potongan/{id}/edit', [jenisPotonganController::class, 'edit'])->name('masterData.keuangan.potongan.edit');
        Route::put('/potongan/{id}/update', [jenisPotonganController::class, 'update'])->name('masterData.keuangan.potongan.update');
        Route::delete('/potongan/{id}/delete', [jenisPotonganController::class, 'destroy'])->name('masterData.keuangan.potongan.delete');

        Route::get('/potonganPegawai', [potonganPegawaiController::class, 'index'])->name('masterData.keuangan.potonganPegawai');
        Route::get('/potonganPegawai/getPotonganPegawaiTable', [potonganPegawaiController::class, 'getPotonganPegawaiTable'])->name('masterData.keuangan.potongan.getPotonganPegawaiTable');
        Route::get('/potonganPegawai/guideJenisPotongan', [potonganPegawaiController::class, 'guideJenisPotongan'])->name('masterData.keuangan.potongan.getGuideJenisPotongan');
        Route::get('/potonganPegawai/exportTemplate', [potonganPegawaiController::class, 'exportTemplate'])->name('masterData.keuangan.potongan.getPotonganPegawaiExportTemplate');
        Route::post('/potonganPegawai/import', [potonganPegawaiController::class, 'importPotonganPegawai'])->name('masterData.keuangan.potongan.importPotonganPegawai');
        Route::get('/potonganPegawai/getPegawai', [potonganPegawaiController::class, 'getPegawai'])->name('masterData.keuangan.potongan.getPegawai');
        Route::post('/potonganPegawai/store', [potonganPegawaiController::class, 'store'])->name('masterData.keuangan.potonganPegawai.store');
        Route::put('/potongan/update-inline/{id}', [potonganPegawaiController::class, 'updateInline'])->name('masterData.keuangan.potonganPegawai.updateInline');
        Route::put('/potongan/bulk-update', [potonganPegawaiController::class, 'bulkUpdate'])->name('masterData.keuangan.potonganPegawai.bulkUpdate');
        Route::delete('/potonganPegawai/{id}/delete', [potonganPegawaiController::class, 'destroy'])->name('masterData.keuangan.potonganPegawai.delete');
        Route::get('/potongan/by-pegawai/{nik}', [potonganPegawaiController::class, 'getByPegawai'])->name('masterData.keuangan.potonganPegawai.getByPegawai');
        Route::post('/potongan/distribusi', [potonganPegawaiController::class, 'distribusi'])->name('masterData.keuangan.potongan.distribusi');
        Route::post('/potongan/preview-distribusi', [potonganPegawaiController::class, 'previewDistribusi'])->name('masterData.keuangan.potongan.previewDistribusi');
        Route::get('/potonganPegawai/getGapokById/{nik}', [potonganPegawaiController::class, 'getGapokById'])->name('masterData.keuangan.potonganPegawai.getGapokById');

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

        Route::get('/skor', [skorController::class, 'skor'])->name('masterData.keuangan.skor');
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

        Route::get('/jnsPremi', [jnsPremiController::class, 'jnsPremi'])->name('masterData.keuangan.jnsPremi');
        Route::get('/jnsPremi/getJnsPremiTable', [jnsPremiController::class, 'jnsPremiTable'])->name('masterData.keuangan.jnsPremi.getJnsPremiTable');
        Route::get('/jnsPremi/generateKode', [jnsPremiController::class, 'generateKodeJnsPremi'])->name('masterData.keuangan.jnsPremi.generateKode');
        Route::post('/jnsPremi/store', [jnsPremiController::class, 'store'])->name('masterData.keuangan.jnsPremi.store');
        Route::get('/jnsPremi/{id}/edit', [jnsPremiController::class, 'edit'])->name('masterData.keuangan.jnsPremi.edit');
        Route::put('/jnsPremi/{id}/update', [jnsPremiController::class, 'update'])->name('masterData.keuangan.jnsPremi.update');
        Route::delete('/jnsPremi/{id}/delete', [jnsPremiController::class, 'destroy'])->name('masterData.keuangan.jnsPremi.delete');

        Route::get('/plotingPremi', [plotingPremiController::class, 'plotingPremi'])->name('masterData.keuangan.plotingPremi');
        Route::get('/plotingPremi/getPlotingPremiTable', [plotingPremiController::class, 'plotingPremiTable'])->name('masterData.keuangan.plotingPremi.getPlotingPremiTable');
        Route::get('/plotingPremi/generateKode', [plotingPremiController::class, 'generateKodePlotingPremi'])->name('masterData.keuangan.plotingPremi.generateKode');
        Route::post('/plotingPremi/store', [plotingPremiController::class, 'store'])->name('masterData.keuangan.plotingPremi.store');
        Route::get('/plotingPremi/{id}/edit', [plotingPremiController::class, 'edit'])->name('masterData.keuangan.plotingPremi.edit');
        Route::put('/plotingPremi/{id}/update', [plotingPremiController::class, 'update'])->name('masterData.keuangan.plotingPremi.update');
        Route::delete('/plotingPremi/{id}/delete', [plotingPremiController::class, 'destroy'])->name('masterData.keuangan.plotingPremi.delete');
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
        Route::get('/mappingTindakan/source-options', [mappingTindakanController::class, 'sourceOptions'])->name('masterData.mapping.mappingTindakan.sourceOptions');
        Route::get('/mappingTindakan/searchTindakan', [mappingTindakanController::class, 'searchTindakan'])->name('masterData.mapping.mappingTindakan.searchTindakan');
        Route::get('/mappingTindakan/by-jenis/{id}', [mappingTindakanController::class, 'getByJenisTindakan'])->name('masterData.mapping.mappingTindakan.byJenis');
        Route::post('/mappingTindakan/store', [mappingTindakanController::class, 'storeTindakan'])->name('masterData.mapping.mappingTindakan.store');
        Route::post('/mappingTindakan/copy', [mappingTindakanController::class, 'copyTindakan'])->name('masterData.mapping.mappingTindakan.copy');
        Route::delete('/mappingTindakan/bulk-delete', [mappingTindakanController::class, 'bulkDestroyTindakan'])->name('masterData.mapping.mappingTindakan.bulkDelete');
        Route::get('/mappingTindakan/{id}/edit', [mappingTindakanController::class, 'editTindakan'])->name('masterData.mapping.mappingTindakan.edit');
        Route::put('/mappingTindakan/{id}/update', [mappingTindakanController::class, 'updateTindakan'])->name('masterData.mapping.mappingTindakan.update');
        Route::delete('/mappingTindakan/{id}/delete', [mappingTindakanController::class, 'destroyTindakan'])->name('masterData.mapping.mappingTindakan.delete');

        Route::get('/mappingPremi', [mappingPremiController::class, 'mappingPremi'])->name('masterData.mapping.mappingPremi');
        Route::get('/mappingPremi/getPremiTable', [mappingPremiController::class, 'premiTable'])->name('masterData.mapping.mappingPremi.getPremiTable');
        Route::get('/mappingPremi/guideJenisPremi', [mappingPremiController::class, 'guideJenisPremi'])->name('masterData.mapping.mappingPremi.guideJenisPremi');
        Route::get('/mappingPremi/guideJenisTindakan', [mappingPremiController::class, 'guideJenisTindakan'])->name('masterData.mapping.mappingPremi.guideJenisTindakan');
        Route::get('/mappingPremi/guidePegawai', [mappingPremiController::class, 'guidePegawai'])->name('masterData.mapping.mappingPremi.guidePegawai');
        Route::get('/mappingPremi/by-premi/{id}', [mappingPremiController::class, 'getByPremi'])->name('masterData.mapping.mappingPremi.byPremi');
        Route::get('/mappingPremi/by-premi/{id}/pegawai', [mappingPremiController::class, 'getPegawaiByPremi'])->name('masterData.mapping.mappingPremi.byPremi.pegawai');
        Route::put('/mappingPremi/by-premi/{id}/pegawai', [mappingPremiController::class, 'updatePegawai'])->name('masterData.mapping.mappingPremi.updatePegawai');
        Route::put('/mappingPremi/by-premi/{id}/pembagi', [mappingPremiController::class, 'updatePembagi'])->name('masterData.mapping.mappingPremi.updatePembagi');
        Route::post('/mappingPremi/store', [mappingPremiController::class, 'store'])->name('masterData.mapping.mappingPremi.store');
        Route::put('/mappingPremi/{id}/update', [mappingPremiController::class, 'update'])->name('masterData.mapping.mappingPremi.update');
        Route::delete('/mappingPremi/{id}/delete', [mappingPremiController::class, 'destroy'])->name('masterData.mapping.mappingPremi.delete');
    });
});

require __DIR__.'/auth.php';
