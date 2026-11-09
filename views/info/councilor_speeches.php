<?php
// ES highlight 片段含 <em>...</em>，其餘文字要跳脫，避免把逐字稿內容當 HTML 注入
function safe_highlight($raw) {
    $parts = preg_split('/(<em>|<\/em>)/u', $raw, -1, PREG_SPLIT_DELIM_CAPTURE);
    return implode('', array_map(function ($part) {
        return ($part === '<em>' || $part === '</em>') ? $part : htmlspecialchars($part);
    }, $parts));
}

$records = $this->councilor_records ?? [];
if (!$records): ?>
<div class="alert alert-warning mt-4"><?= htmlspecialchars(CouncilHelper::t('找不到議員資料')) ?></div>
<?php return; endif;

$person_code = $records[0]->{'人物代碼'} ?? '';
$term = $this->speech_term ?? null;
$total = $this->speech_total ?? 0;
$groups = $this->speech_groups ?? [];
$sessions = $this->speech_sessions ?? [];
$session = $this->speech_session ?? '';
$session_counts = array_column(array_map(fn($s) => (array)$s, $sessions), 'count', '會期代碼');
$all_total = $this->speech_all_total ?? $total;
$page = $this->speech_page ?? 1;
$total_page = $this->speech_total_page ?? 0;
$speech_url = function ($session_code, $page_no) use ($person_code, $term) {
    $q = ['term' => (int)$term];
    if ($session_code !== '') $q['session'] = $session_code;
    if ($page_no > 1) $q['page'] = $page_no;
    return '/info/councilor/' . urlencode($person_code) . '/speeches?' . http_build_query($q);
};
?>

<?php if (count($records) > 1): ?>
<div class="mb-3">
  <span class="small text-body-secondary me-2">選擇屆次：</span>
  <?php foreach ($records as $r): ?>
  <a href="/info/councilor/<?= urlencode($person_code) ?>/speeches?term=<?= (int)$r->{'屆次'} ?>"
     class="badge <?= ((int)$r->{'屆次'} === (int)$term) ? 'bg-primary' : 'bg-light text-dark border' ?> me-1">
    第 <?= (int)$r->{'屆次'} ?> 屆
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!$term): ?>
<div class="alert alert-light border">找不到可查詢的屆期資料</div>
<?php else: ?>

<p class="small text-body-secondary">
  第 <?= (int)$term ?> 屆・共找到 <?= number_format($all_total) ?> 筆發言記錄，來自 <?= count($sessions) ?> 個會期（依日期新到舊排列）
</p>

<?php if (count($sessions) > 1): ?>
<div class="d-flex flex-wrap gap-1 mb-3">
  <a href="<?= htmlspecialchars($speech_url('', 1)) ?>" class="badge <?= $session === '' ? 'bg-primary' : 'bg-light text-dark border' ?>">全部 <?= number_format($all_total) ?></a>
  <?php foreach ($sessions as $s): ?>
  <a href="<?= htmlspecialchars($speech_url($s->{'會期代碼'}, 1)) ?>" class="badge <?= $session === $s->{'會期代碼'} ? 'bg-primary' : 'bg-light text-dark border' ?>"><?= htmlspecialchars($s->{'會期名稱'}) ?> <?= number_format($s->count) ?></a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (empty($groups)): ?>
<?php if ($this->speech_no_coverage ?? false): ?>
<div class="alert alert-warning border">
  本議會的逐字稿尚未收錄，不代表<?= htmlspecialchars(CouncilHelper::t('議員')) ?>沒有發言。
  <?php if ($this->speech_no_coverage_note ?? null): ?>
  <div class="small mt-1"><?= htmlspecialchars($this->speech_no_coverage_note) ?></div>
  <?php endif; ?>
</div>
<?php else: ?>
<div class="alert alert-light border">這屆沒有找到發言記錄</div>
<?php endif; ?>
<?php else: ?>
<?php foreach ($groups as $group): ?>
<div class="card shadow-sm mb-3">
  <div class="card-header py-2 d-flex align-items-center justify-content-between">
    <strong class="small"><?= htmlspecialchars($group->{'會期名稱'}) ?></strong>
    <span class="text-body-secondary small"><?= number_format($session_counts[$group->{'會期代碼'}] ?? count($group->items)) ?> 筆發言</span>
  </div>
  <div class="list-group list-group-flush">
    <?php foreach ($group->items as $t): ?>
    <?php
      $highlight = $t->{'發言內容:highlight'} ?? [];
      $href = '/info/' . (int)$term . '/meet/' . urlencode($t->{'會議代碼'}) . '#speech-' . urlencode($t->{'代碼'});
    ?>
    <a href="<?= htmlspecialchars($href) ?>" class="list-group-item list-group-item-action">
      <div class="small text-body-secondary mb-1">
        <?= htmlspecialchars($t->{'日期'} ?? '') ?>
        <?php if ($t->{'會議主旨'} ?? null): ?>
        ・<?= htmlspecialchars($t->{'會議主旨'}) ?>
        <?php endif; ?>
      </div>
      <?php if ($highlight): ?>
      <?php foreach (array_slice($highlight, 0, 2) as $h): ?>
      <div class="small"><?= safe_highlight($h) ?></div>
      <?php endforeach; ?>
      <?php else: ?>
      <div class="small"><?= htmlspecialchars(mb_substr($t->{'發言內容'} ?? '', 0, 80)) ?></div>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endforeach; ?>
<?php if ($total_page > 1): ?>
<div class="d-flex align-items-center gap-2 small">
  <a class="btn btn-sm btn-outline-secondary<?= $page <= 1 ? ' disabled' : '' ?>" href="<?= htmlspecialchars($speech_url($session, max(1, $page - 1))) ?>">上一頁</a>
  <span>第 <?= number_format($page) ?> / <?= number_format($total_page) ?> 頁</span>
  <a class="btn btn-sm btn-outline-secondary<?= $page >= $total_page ? ' disabled' : '' ?>" href="<?= htmlspecialchars($speech_url($session, $page + 1)) ?>">下一頁</a>
</div>
<?php endif; ?>
<?php endif; ?>

<?php endif; ?>
