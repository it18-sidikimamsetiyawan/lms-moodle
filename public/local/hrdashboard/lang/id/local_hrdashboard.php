<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Indonesian strings for local_hrdashboard.
 *
 * @package    local_hrdashboard
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'HR Learning Dashboard';
$string['privacy:metadata'] = 'HR Learning Dashboard hanya mengagregasi data yang sudah disimpan Moodle dan tidak menyimpan data pribadi sendiri.';
$string['hrdashboard:viewown'] = 'Melihat dashboard belajar sendiri';
$string['hrdashboard:viewhr'] = 'Melihat HR learning dashboard (progress user lain)';
$string['hrdashboard:export'] = 'Mengekspor laporan HR learning';
$string['task:refreshstats'] = 'Perbarui statistik HR learning dashboard';

$string['hrdashboard'] = 'HR Dashboard';
$string['hrdashboardtitle'] = 'HR Learning Dashboard';
$string['mylearning'] = 'My Learning';
$string['mylearningtitle'] = 'My Learning Dashboard';
$string['coursemonitoring'] = 'Monitoring Course';
$string['employeeprogress'] = 'Progress Karyawan';
$string['employeedetail'] = 'Detail karyawan';
$string['coursedetail'] = 'Detail course';
$string['backtodashboard'] = 'Kembali ke HR Dashboard';
$string['openhrdashboard'] = 'Buka HR Dashboard';

$string['settings:scope'] = 'Cakupan';
$string['settings:attributes'] = 'Atribut karyawan';
$string['settings:attributes_desc'] = 'Sumber data Department, Division, dan Position. Pilih field user inti atau custom profile field (teks/menu). Atribut yang "Tidak ada" disembunyikan dari filter dan tabel. Sinkronisasi HRIS dapat mengisi field ini kemudian tanpa mengubah dashboard.';
$string['settings:risk'] = 'Aturan At Risk';
$string['settings:risk_desc'] = 'Karyawan dianggap "At Risk" pada sebuah course bila salah satu aturan di bawah terpenuhi. Isi 0 untuk mematikan aturan. Alasannya selalu ditampilkan ke HR.';
$string['settings:performance'] = 'Performa dan tampilan';
$string['settings:links'] = '<a href="{$a->hr}">Buka HR Dashboard</a> · <a href="{$a->my}">Buka My Learning</a> · <a href="{$a->roles}">Kelola role</a> (beri <em>local/hrdashboard:viewhr</em> di level system untuk semua karyawan, atau di sebuah course category untuk cakupan terbatas)';
$string['categoryscope'] = 'Cakupan course category';
$string['categoryscope_desc'] = 'Hanya course dalam kategori ini (dan sub-kategorinya) yang dimonitor. Kosongkan untuk semua kategori.';
$string['employeesource'] = 'Populasi karyawan';
$string['employeesource_desc'] = 'Siapa yang dihitung sebagai karyawan pada KPI HR. Site administrator, guest, akun terhapus, dan service account selalu dikecualikan.';
$string['employeesource:all'] = 'Semua user terkonfirmasi';
$string['employeesource:enrolled'] = 'User yang terenrol di course yang dimonitor';
$string['employeesource:cohort'] = 'Anggota sebuah cohort';
$string['employeecohort'] = 'Cohort karyawan';
$string['employeecohort_desc'] = 'Dipakai bila populasi karyawan = "Anggota sebuah cohort" (misalnya cohort yang diisi sinkronisasi HRIS).';
$string['includehidden'] = 'Sertakan course tersembunyi';
$string['includehidden_desc'] = 'Ikut memonitor course yang disembunyikan dari learner.';
$string['attr:department'] = 'Department';
$string['attr:division'] = 'Division';
$string['attr:position'] = 'Position';
$string['attrfield_desc'] = 'Field sumber atribut ini.';
$string['source:department'] = 'Field user inti: Department';
$string['source:institution'] = 'Field user inti: Institution';
$string['source:profile'] = 'Profile field';
$string['none'] = 'Tidak ada';
$string['inactivedays'] = 'Hari tidak aktif';
$string['inactivedays_desc'] = 'Tandai course yang belum selesai bila karyawan tidak beraktivitas di dalamnya lebih dari N hari.';
$string['nologindays'] = 'Hari tidak login';
$string['nologindays_desc'] = 'Tandai karyawan dengan course belum selesai yang tidak login lebih dari N hari. Juga menentukan jendela "Karyawan aktif".';
$string['notstarteddays'] = 'Hari belum mulai';
$string['notstarteddays_desc'] = 'Tandai enrolment yang belum dimulai N hari setelah enrolment.';
$string['lowprogress'] = 'Progress rendah (%)';
$string['lowprogress_desc'] = 'Tandai bila progress di bawah persentase ini dan due date (atau tanggal akhir course) berada dalam jendela deadline.';
$string['deadlinedays'] = 'Jendela deadline (hari)';
$string['deadlinedays_desc'] = 'Jumlah hari sebelum due date di mana progress rendah menjadi risiko.';
$string['pendingthreshold'] = 'Ambang pending activities';
$string['pendingthreshold_desc'] = 'Tandai bila jumlah pending activities di satu course mencapai nilai ini. 0 = nonaktif.';
$string['cachettl'] = 'Cache statistik (menit)';
$string['cachettl_desc'] = 'Berapa lama statistik HR dipakai ulang sebelum dihitung ulang. 0 = hitung ulang setiap halaman dibuka (tidak disarankan untuk situs besar). HR selalu bisa memaksa hitung ulang dengan tombol Refresh.';
$string['trendweeks'] = 'Tren completion (minggu)';
$string['trendweeks_desc'] = 'Jumlah minggu pada grafik tren completion.';
$string['timelinedays'] = 'Riwayat timeline (hari)';
$string['timelinedays_desc'] = 'Jumlah hari aktivitas yang ditampilkan pada timeline karyawan.';

$string['status:notstarted'] = 'Belum mulai';
$string['status:inprogress'] = 'Sedang berjalan';
$string['status:atrisk'] = 'At risk';
$string['status:overdue'] = 'Overdue';
$string['status:completed'] = 'Selesai';
$string['risk:overdue'] = '{$a} activity melewati due date';
$string['risk:courseended'] = 'Course berakhir {$a} hari lalu, belum selesai';
$string['risk:lowprogress'] = 'Progress {$a->progress}% dengan deadline {$a->days} hari lagi';
$string['risk:inactive'] = 'Tidak ada aktivitas di course ini selama {$a} hari';
$string['risk:notstarted'] = 'Terenrol {$a} hari lalu, belum mulai';
$string['risk:nologin'] = 'Tidak login selama {$a} hari';
$string['risk:neverloggedin'] = 'Belum pernah login ({$a} hari sejak akun dibuat)';
$string['risk:pending'] = '{$a} pending activities';

$string['kpi:totalemployees'] = 'Total karyawan';
$string['kpi:activeemployees'] = 'Karyawan aktif';
$string['kpi:activewindow'] = 'login dalam {$a} hari';
$string['kpi:withactivity'] = 'Punya aktivitas belajar';
$string['kpi:noactivity'] = 'Tanpa aktivitas';
$string['kpi:enrolled'] = 'Karyawan terenrol';
$string['kpi:enrolments'] = '{$a} enrolment';
$string['kpi:completionrate'] = 'Course completion rate';
$string['kpi:completedof'] = '{$a->completed} dari {$a->total} enrolment';
$string['kpi:avgprogress'] = 'Rata-rata progress';
$string['kpi:avgscore'] = 'Rata-rata skor';
$string['kpi:overdue'] = 'Activity overdue';
$string['kpi:atrisk'] = 'Karyawan at risk';
$string['kpi:mycourses'] = 'Course saya';
$string['kpi:completed'] = 'Selesai';
$string['kpi:inprogress'] = 'Sedang berjalan';
$string['kpi:notstarted'] = 'Belum mulai';
$string['kpi:overallprogress'] = 'Progress keseluruhan';
$string['kpi:pending'] = 'Pending activities';
$string['kpi:overduecount'] = '{$a} overdue';
$string['kpi:tracked'] = 'Activity terlacak';

$string['section:coursecompletion'] = 'Ringkasan completion per course';
$string['section:departmentprogress'] = 'Progress per {$a}';
$string['section:statusdistribution'] = 'Distribusi status karyawan';
$string['section:completiontrend'] = 'Tren completion';
$string['section:coursemonitoring'] = 'Monitoring course';
$string['section:atrisk'] = 'Karyawan at risk';
$string['section:recent'] = 'Aktivitas belajar terbaru';
$string['section:activities'] = 'Activity';
$string['section:employees'] = 'Karyawan';
$string['section:courses'] = 'Course';
$string['section:summary'] = 'Ringkasan belajar saya';
$string['section:continue'] = 'Lanjutkan belajar';
$string['section:pending'] = 'Pending activities';
$string['section:myprogress'] = 'Progress saya';
$string['section:timeline'] = 'Aktivitas terbaru';

$string['col:course'] = 'Course';
$string['col:category'] = 'Kategori';
$string['col:enrolled'] = 'Terenrol';
$string['col:notstarted'] = 'Belum mulai';
$string['col:inprogress'] = 'Berjalan';
$string['col:atrisk'] = 'At risk';
$string['col:overdue'] = 'Overdue';
$string['col:completed'] = 'Selesai';
$string['col:completionrate'] = 'Completion %';
$string['col:avgprogress'] = 'Rata-rata progress';
$string['col:employee'] = 'Karyawan';
$string['col:progress'] = 'Progress';
$string['col:score'] = 'Skor';
$string['col:lastactivity'] = 'Aktivitas terakhir';
$string['col:status'] = 'Status';
$string['col:activity'] = 'Activity';
$string['col:type'] = 'Tipe';
$string['col:due'] = 'Due date';
$string['col:action'] = 'Aksi';
$string['col:reasons'] = 'Alasan';
$string['col:courses'] = 'Course';
$string['col:completedon'] = 'Selesai pada';
$string['col:grade'] = 'Nilai';
$string['col:section'] = 'Section';
$string['col:completion'] = 'Completion';
$string['col:pending'] = 'Pending';
$string['col:enrolledon'] = 'Terenrol pada';
$string['col:lastlogin'] = 'Login terakhir';
$string['col:employees'] = 'Karyawan';
$string['col:when'] = 'Waktu';

$string['filters'] = 'Filter';
$string['filter:category'] = 'Kategori';
$string['filter:course'] = 'Course';
$string['filter:status'] = 'Status';
$string['filter:search'] = 'Cari karyawan';
$string['filter:from'] = 'Aktivitas dari';
$string['filter:to'] = 'Aktivitas sampai';
$string['filter:all'] = 'Semua';
$string['apply'] = 'Terapkan';
$string['reset'] = 'Reset';
$string['refresh'] = 'Refresh data';
$string['refreshed'] = 'Statistik dihitung ulang.';
$string['export'] = 'Export';
$string['export:csv'] = 'Export CSV';
$string['export:excel'] = 'Export Excel';
$string['export:pdf'] = 'Export PDF';
$string['exportfilename'] = 'progress-belajar-karyawan';
$string['viewdetail'] = 'Lihat detail';
$string['viewall'] = 'Lihat semua';
$string['viewcourse'] = 'Buka course';
$string['start'] = 'Mulai';
$string['continue'] = 'Lanjutkan';
$string['continuelearning'] = 'Lanjutkan belajar';
$string['showing'] = 'Menampilkan {$a->from}–{$a->to} dari {$a->total}';

$string['never'] = 'Belum pernah';
$string['nodue'] = 'Tanpa due date';
$string['nodata'] = 'Tidak ada data untuk pilihan ini.';
$string['nocourses'] = 'Belum ada course yang dimonitor dalam cakupan Anda.';
$string['nocoursesyet'] = 'Anda belum terenrol di course mana pun.';
$string['nopending'] = 'Tidak ada yang pending — semua sudah selesai.';
$string['notimeline'] = 'Tidak ada aktivitas belajar dalam {$a} hari terakhir.';
$string['noatrisk'] = 'Saat ini tidak ada karyawan yang at risk.';
$string['norecent'] = 'Belum ada completion tercatat.';
$string['noemployees'] = 'Tidak ada karyawan yang cocok dengan filter.';
$string['generatedat'] = 'Data per {$a}';
$string['activitiescompleted'] = '{$a->done} / {$a->total} activity';
$string['activitiescompletedlong'] = 'Activity selesai: {$a->done} / {$a->total}';
$string['lastactivitylabel'] = 'Aktivitas terakhir';
$string['nextactivitylabel'] = 'Activity berikutnya';
$string['completionnotenabled'] = 'Completion tracking tidak aktif pada course ini.';
$string['notracked'] = 'Tidak ada activity dengan completion tracking.';
$string['streak'] = 'Learning streak';
$string['streakdays'] = '{$a} hari berturut-turut';
$string['completedvspending'] = 'Activity selesai vs pending';
$string['series:activities'] = 'Activity selesai';
$string['series:courses'] = 'Course selesai';
$string['series:avgprogress'] = 'Rata-rata progress (%)';
$string['series:completionrate'] = 'Completion rate (%)';
$string['series:employees'] = 'Karyawan';
$string['unspecified'] = 'Tidak diisi';
$string['yesterday'] = 'Kemarin';
$string['timeline:completed'] = 'Menyelesaikan {$a->type} – {$a->activity}';
$string['timeline:viewed'] = 'Membuka {$a->type} – {$a->activity}';
$string['timeline:coursecompleted'] = 'Menyelesaikan course – {$a}';
$string['recent:completed'] = '{$a->user} menyelesaikan {$a->activity} di {$a->course}';
$string['gradehidden'] = 'Disembunyikan';
$string['hiddencourse'] = 'Course tersembunyi';
$string['notinscope'] = 'Karyawan atau course ini berada di luar cakupan HR Anda.';
$string['state:complete'] = 'Selesai';
$string['state:pass'] = 'Selesai (lulus)';
$string['state:fail'] = 'Selesai (gagal)';
$string['state:incomplete'] = 'Belum selesai';
$string['courseprogress'] = 'Progress course';
$string['employeecount'] = '{$a} karyawan';
$string['scopelimited'] = 'Cakupan HR Anda dibatasi pada {$a} course category.';
$string['employeesince'] = 'Akun dibuat {$a}';
$string['allcourses'] = 'Semua course';
$string['chart:statuslabel'] = 'Enrolment';
$string['chart:week'] = 'Minggu {$a}';

// Modernisasi UI (2026-10).
$string['subtitle:hr'] = 'Pantau performa belajar karyawan dalam cakupan Anda.';
$string['subtitle:my'] = 'Ini progress belajar Anda.';
$string['greeting:morning'] = 'Selamat pagi, {$a} 👋';
$string['greeting:afternoon'] = 'Selamat siang, {$a} 👋';
$string['greeting:evening'] = 'Selamat malam, {$a} 👋';
$string['filters:more'] = 'Filter lainnya';
$string['filters:clearall'] = 'Hapus semua';
$string['filters:active'] = 'Filter';
$string['filters:remove'] = 'Hapus filter: {$a}';
$string['filter:searchplaceholder'] = 'Cari karyawan…';
$string['filter:allcategories'] = 'Semua kategori';
$string['filter:allcourses'] = 'Semua course';
$string['filter:allstatuses'] = 'Semua status';
$string['filter:daterange'] = 'Aktivitas {$a->from} – {$a->to}';
$string['due:today'] = 'Jatuh tempo hari ini';
$string['due:tomorrow'] = 'Jatuh tempo besok';
$string['due:in'] = 'Jatuh tempo {$a} hari lagi';
$string['due:on'] = 'Jatuh tempo {$a}';
$string['due:overdueby'] = 'Terlambat {$a} hari';
$string['due:none'] = 'Tanpa due date';
$string['empty:noatrisktitle'] = 'Tidak ada karyawan at risk';
$string['empty:noatrisktext'] = 'Semua karyawan saat ini sesuai jalur.';
$string['empty:nopendingtitle'] = 'Tidak ada pending activity';
$string['empty:nopendingtext'] = 'Semua sudah beres 🎉';
$string['empty:nodatatitle'] = 'Tidak ada data untuk filter ini';
$string['empty:nodatatext'] = 'Coba ubah atau hapus filter.';
$string['empty:norecenttitle'] = 'Belum ada completion terbaru';
$string['empty:norecenttext'] = 'Activity yang selesai akan muncul di sini.';
$string['empty:notimelinetitle'] = 'Belum ada aktivitas terbaru';
$string['empty:notimelinetext'] = 'Aktivitas belajar Anda {$a} hari terakhir akan muncul di sini.';
$string['empty:nocoursestitle'] = 'Belum ada course';
$string['empty:nocoursestext'] = 'Course yang Anda ikuti akan muncul di sini.';
$string['empty:noemployeestitle'] = 'Tidak ada karyawan yang cocok';
$string['empty:noemployeestext'] = 'Coba course, status, atau kata kunci lain.';
$string['empty:allcompletedtitle'] = 'Semua course selesai';
$string['empty:allcompletedtext'] = 'Tidak ada yang perlu dilanjutkan — kerja bagus.';
$string['empty:noactivitiestitle'] = 'Tidak ada activity terlacak';
$string['empty:noactivitiestext'] = 'Course ini tidak punya activity dengan completion tracking.';
$string['chart:completiondesc'] = 'Jumlah karyawan per status di tiap course';
$string['chart:statusdesc'] = 'Seluruh enrolment dalam cakupan, per status';
$string['chart:trenddesc'] = 'Activity dan course yang selesai per minggu';
$string['chart:attributedesc'] = 'Rata-rata progress dan completion rate per {$a}';
$string['section:coursemonitoringdesc'] = 'Rincian completion dan status per course';
$string['section:atriskdesc'] = 'Siapa yang perlu perhatian, dan alasannya';
$string['section:recentdesc'] = 'Activity terbaru yang selesai dalam cakupan Anda';
$string['section:continuedesc'] = 'Lanjutkan dari posisi terakhir';
$string['section:pendingdesc'] = 'Due date terdekat lebih dulu';
$string['section:employeesdesc'] = '{$a} enrolment';
$string['section:activitiesdesc'] = 'Completion per activity di antara karyawan yang tampil';
$string['col:breakdown'] = 'Rincian';
$string['sortby'] = 'Urutkan {$a}';
$string['viewprogress'] = 'Lihat progress';
$string['andmore'] = '+{$a} lagi';
$string['hero:label'] = 'Progress belajar keseluruhan';
$string['hero:activities'] = '<strong>{$a->done}</strong> dari {$a->total} activity selesai';
$string['hero:courses'] = '<strong>{$a->completed}</strong> dari {$a->total} course selesai';
$string['hero:pending'] = '<strong>{$a}</strong> pending';
$string['streak:text'] = 'Belajar setiap hari untuk memperpanjang streak';
$string['streak:none'] = 'Buka satu activity hari ini untuk memulai streak';
$string['kpi:completedcourses'] = 'Course selesai';
$string['kpi:inprogresscourses'] = 'Sedang berjalan';
$string['scopeall'] = 'Semua kategori';
$string['lastactivityshort'] = 'Terakhir';
$string['nextactivityshort'] = 'Berikutnya';
$string['dueshort'] = 'Due';
$string['completedsummary'] = '{$a} selesai';
$string['inprogresssummary'] = '{$a} berjalan';
$string['atrisksummary'] = '{$a} at risk';
$string['overduesummary'] = '{$a} overdue';
$string['notstartedsummary'] = '{$a} belum mulai';
$string['activitiessummary'] = '{$a} activity terlacak';
$string['employeesinscope'] = '{$a} karyawan dalam cakupan';
