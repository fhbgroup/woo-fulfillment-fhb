<?php if (!defined( 'KIKA_PLUGIN_URL')) exit; ?>

<table class="form-table">
	<tr>
		<th>Objednávky</th>
		<td>
			<span class="text-success"><?php echo $countSynced ?></span> / <span class="text-danger"><?php echo $countError ?></span> / <?php echo $count ?>
		</td>
	</tr>
</table>

<small>(V stave spracováva sa)</small>