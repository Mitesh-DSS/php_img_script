<?php

function downloadImage($imageURL, $filePath) {
    if (empty($imageURL)) {
        throw new Exception("Image URL is empty or invalid.");
    }

    $ch = curl_init($imageURL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $imageData = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new Exception("Failed to download image: $curlError");
    }

    if (file_put_contents($filePath, $imageData) === false) {
        throw new Exception("Failed to save downloaded image to '$filePath'");
    }

    return true;
}

function createImageFromCSV($csvFile, $outputFolder) {
  $rows = array_map('str_getcsv', file($csvFile));
  $header = array_shift($rows);
  $fontPath = './font/arial.ttf';
  foreach ($rows as $row) {
      $name = $row[0];
      $position = $row[1];
      $work = $row[2];
      $imageURL = $row[3];

      $imageName = basename($imageURL);
      $imagePath = $outputFolder . '/' . $imageName;
      downloadImage($imageURL, $imagePath);
      $original_path = "./$outputFolder/$imageName";
      list($width, $height) = getimagesize($original_path); 
      $nwidth = 200;
      $nheight = 200;
      $newimage = imagecreatetruecolor($nwidth, $nheight);
      $source = imagecreatefromjpeg($original_path);
      imagecopyresized($newimage, $source, 0, 0, 0, 0, $nwidth, $nheight, $width, $height);
      imagejpeg($newimage, $original_path);
      $src = imagecreatefromstring(file_get_contents($original_path));
      $newpic = imagecreatefromjpeg($original_path);
      $black = imagecolorallocate($newpic, 0, 0, 0);
      imagealphablending($newpic, false);
      $transparent = imagecolorallocatealpha($newpic, 0, 0, 0, 127);
      $w = 200;
      $h = 200;
      $r = $w / 2;
      for ($x = 0; $x < $w; $x++){
          for ($y = 0; $y < $h; $y++) {
              $_x = $x - $w / 2;
              $_y = $y - $h / 2;
              if (!((($_x * $_x) + ($_y * $_y)) < ($r * $r))) {
                  imagesetpixel($newpic, $x, $y, $transparent);
                } 
            }
        }
      imagesavealpha($newpic, true);
      imagepng($newpic, $original_path);

      $font_size = 15;
      $name_lines = explode('|', wordwrap($name, 40, '|'));
      $position_lines = explode('|', wordwrap($position, 40, '|'));
      $work_lines = explode('|', wordwrap($work, 40, '|'));

      $name_y = 100;
      $position_y = 200;
      $work_y = 300;

      foreach ($name_lines as $line) {
          imagettftext($newpic, $font_size, 0, 400, $name_y, $black, $fontPath, $line);
          $name_y += 23;
        }
      foreach ($position_lines as $line) {
          imagettftext($newpic, $font_size, 0, 400, $position_y, $black, $fontPath, $line);
          $position_y += 23;
        }
      foreach ($work_lines as $line) {
          imagettftext($newpic, $font_size, 0, 400, $work_y, $black, $fontPath, $line);
          $work_y += 23;
        }
      imagepng($newpic, $original_path);
      imagedestroy($newpic);
      imagedestroy($src);
    }
}


$csvFile = 'images.csv';
$outputFolder = 'Output';

if (!file_exists($outputFolder)) {
    if (!mkdir($outputFolder, 0777, true)) {
        echo "Error: Failed to create output directory '$outputFolder'";
        exit;
    }
} elseif (!is_writable($outputFolder)) {
    echo "Error: Output folder '$outputFolder' is not writable.";
    exit;
}

createImageFromCSV($csvFile, $outputFolder);
echo "Images created successfully!";
