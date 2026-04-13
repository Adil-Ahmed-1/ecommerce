<?php
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']); // ek baar dikhao phir hatado
?>
<div id="flashMsg" class="fixed top-24 right-6 z-[9999] flex items-center gap-3 px-5 py-4 rounded-2xl shadow-2xl border max-w-sm
    <?= $flash['type'] === 'success'
        ? 'bg-[#101018] border-gold-500/20'
        : 'bg-[#101018] border-red-500/20'
    ?>"
    style="animation: slideIn 0.4s cubic-bezier(.4,0,.2,1) forwards;"
>

    <div class="w-10 h-10 rounded-xl shrink-0 flex items-center justify-center
        <?= $flash['type'] === 'success'
            ? 'bg-gradient-to-br from-gold-400 to-gold-600'
            : 'bg-gradient-to-br from-red-400 to-red-600'
        ?>">
        <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-check' : 'fa-xmark' ?> text-surface-900 text-sm"></i>
    </div>

    <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold text-white"><?= $flash['msg'] ?></p>
        <?php if (!empty($flash['extra'])) { ?>
            <p class="text-xs text-white/30 mt-0.5"><?= $flash['extra'] ?></p>
        <?php } ?>
    </div>

    <button onclick="dismissFlash()" class="w-7 h-7 rounded-lg hover:bg-white/5 flex items-center justify-center text-white/30 hover:text-white/60 transition shrink-0">
        <i class="fa-solid fa-xmark text-xs"></i>
    </button>

</div>

<style>
@keyframes slideIn {
    from { opacity:0; transform:translateX(20px) scale(0.96); }
    to { opacity:1; transform:translateX(0) scale(1); }
}
@keyframes slideOut {
    to { opacity:0; transform:translateX(20px) scale(0.96); }
}
</style>

<script>
function dismissFlash() {
    const el = document.getElementById('flashMsg');
    if (!el) return;
    el.style.animation = 'slideOut 0.3s ease forwards';
    setTimeout(() => el.remove(), 300);
}
setTimeout(dismissFlash, 3500);
</script>

<?php } ?>