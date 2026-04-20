<?php
declare(strict_types=1);
$asHtml = (bool)($pdfContext['asHtml'] ?? false);
$authName = (string)($_COOKIE['auth_name'] ?? '');
?>
<div class="footer-block">
<div class="section">
    <br><br>

    <strong>For any queries, please get in touch with us:</strong><br><br>

    <?= h($authName !== '' ? $authName : 'Smartronic') ?><br>
    hello@smartronic.online<br><br>

    www.smartronic.online<br>
    888 483 1000
</div>

<hr class="brand-rule-red" />

<table class="footer-contact" width="80%" align="center" cellspacing="0" cellpadding="0">
  <tr>
    <td class="footer-contact-logo" width="12%" valign="middle" align="right">
      <img class="footer-logo-img" src="https://www.smartronic.online/content/uploads/2025/01/smartronic_small_logo.jpg" alt="Smartronic" />
    </td>
    <td width="88%" valign="middle" align="left" style="font-size: 11px;">
      Mobile: 8496080849 | hello@smartronic.online | www.smartronic.online<br>
      809 A, 25th Cross, Sector 2, HSR Layout, Bangalore 560102
    </td>
  </tr>
</table>
 </div>

<?php if ($asHtml): ?></div><?php endif; ?>
</body>
</html>

