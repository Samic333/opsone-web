<?php
/** @var array $data */
$pageTitle = 'Engineer Dashboard';
ob_start();
?>

<div class="space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white tracking-tight">Welcome, <?= e($_SESSION['user']['name']) ?></h1>
            <p class="text-slate-400">Engineering Operations — <?= e($_SESSION['tenant']['name'] ?? 'OpsOne') ?></p>
        </div>
        <div class="px-4 py-2 bg-slate-800/50 rounded-xl border border-slate-700/50 backdrop-blur-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-widest block">Last iPad Sync</span>
            <span class="text-sm font-medium text-emerald-400"><?= $data['sync_status'] ? date('d M H:i', strtotime($data['sync_status'])) : 'Never' ?></span>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-slate-900/50 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl">
            <div class="flex items-center gap-4 mb-4">
                <div class="p-3 bg-emerald-500/10 rounded-2xl text-emerald-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="font-semibold text-white">Status</h3>
            </div>
            <div class="text-2xl font-bold text-white mb-1">On Shift</div>
            <p class="text-sm text-slate-500">NBO Maintenance Bay</p>
        </div>

        <div class="bg-slate-900/50 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl">
            <div class="flex items-center gap-4 mb-4">
                <div class="p-3 bg-blue-500/10 rounded-2xl text-blue-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <h3 class="font-semibold text-white">Open Tasks</h3>
            </div>
            <div class="text-2xl font-bold text-white mb-1">—</div>
            <p class="text-sm text-slate-500">Task tracking coming soon</p>
        </div>

        <div class="bg-slate-900/50 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl">
            <div class="flex items-center gap-4 mb-4">
                <div class="p-3 bg-purple-500/10 rounded-2xl text-purple-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                </div>
                <h3 class="font-semibold text-white">Documents</h3>
            </div>
            <div class="text-2xl font-bold text-white mb-1"><?= $data['total_files'] ?></div>
            <p class="text-sm text-slate-500">Published manuals</p>
        </div>
    </div>

    <!-- Recent Notices -->
    <div class="bg-slate-900/50 border border-slate-800 rounded-3xl overflow-hidden backdrop-blur-xl">
        <div class="p-6 border-b border-slate-800 flex justify-between items-center">
            <h3 class="font-bold text-white uppercase text-sm tracking-wider">Engineering Notices</h3>
            <a href="/notices" class="text-blue-400 text-xs font-semibold hover:text-blue-300 transition-colors">VIEW ALL</a>
        </div>
        <div class="divide-y divide-slate-800">
            <?php foreach ($data['recent_notices'] as $notice): ?>
                <div class="p-6 hover:bg-slate-800/30 transition-colors group cursor-pointer">
                    <div class="flex justify-between items-start mb-2">
                        <h4 class="font-bold text-white group-hover:text-blue-400 transition-colors"><?= e($notice['title']) ?></h4>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest border
                            <?= $notice['priority'] === 'urgent' ? 'bg-amber-500/10 text-amber-500 border-amber-500/20' :
                                ($notice['priority'] === 'critical' ? 'bg-red-500/10 text-red-500 border-red-500/20' :
                                'bg-slate-500/10 text-slate-400 border-slate-500/20') ?>">
                            <?= e($notice['priority']) ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($data['recent_notices'])): ?>
                <div class="p-12 text-center">
                    <p class="text-slate-500">No active notices.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sync Reminder -->
    <div class="p-8 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-3xl text-white">
        <h3 class="text-xl font-bold mb-2">Sync your iPad</h3>
        <p class="text-blue-100 mb-6 max-w-lg">Keep your CrewAssist app up to date for the latest engineering orders and manuals.</p>
        <a href="/install" class="inline-flex items-center px-6 py-3 bg-white text-blue-600 rounded-2xl font-bold hover:bg-blue-50 transition-colors">
            Get iPad Build
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
