<?php
include 'db.php';
$id = intval($_GET['id']);
$ep = $_GET['ep'] ?? '';
$stmt = $conn->prepare("SELECT m.*, ml.google_drive_file_id, ml.ep_name, ml.media_id FROM media_files m LEFT JOIN media_links ml ON m.id = ml.media_id WHERE m.id = ? AND ml.ep_name = ?");
$stmt->bind_param("is", $id, $ep);
$stmt->execute();
$media = $stmt->get_result()->fetch_assoc();
if (!$media) { echo "<div>ไม่พบข้อมูล</div>"; exit; }
$file_id = $media['google_drive_file_id'];
$preview_url = "https://drive.google.com/file/d/" . $file_id . "/preview";
?>
<div>
  <div class="mb-2 font-bold"><?php echo htmlspecialchars($media['title']); ?> <?php echo htmlspecialchars($media['ep_name']); ?></div>
  <?php if ($media['file_type'] === 'video'): ?>
    <div class="relative w-full mb-2" style="padding-top:56.25%;">
      <iframe src="<?php echo $preview_url; ?>" class="absolute top-0 left-0 w-full h-full border-0 rounded" allowfullscreen></iframe>
    </div>
  <?php elseif ($media['file_type'] === 'pdf'): ?>
    <iframe src="<?php echo $preview_url; ?>" width="100%" height="300" class="border-0 rounded mb-2" allowfullscreen></iframe>
  <?php elseif ($media['file_type'] === 'word'): ?>
    <iframe src="https://docs.google.com/document/d/<?php echo $file_id; ?>/preview" width="100%" height="300" class="border-0 rounded mb-2" allowfullscreen></iframe>
  <?php elseif ($media['file_type'] === 'image'): ?>
    <div class="flex justify-center items-center h-48 bg-gray-100 mb-2">
      <img src="https://drive.google.com/uc?export=view&id=<?php echo $file_id; ?>" class="max-w-full max-h-48 mx-auto rounded" alt="">
    </div>
  <?php endif; ?>
  <?php if($media['document_references']): ?>
    <div class="mb-2 text-sm text-gray-600"><?php echo htmlspecialchars($media['document_references']); ?></div>
  <?php endif; ?>
  <?php if($media['illustrations']): ?>
    <img src="<?php echo htmlspecialchars($media['illustrations']); ?>" class="max-w-full h-32 rounded shadow mb-2">
  <?php endif; ?>
</div> 