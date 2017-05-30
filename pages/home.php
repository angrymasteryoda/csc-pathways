<?php
	/**
	 * Created by IntelliJ IDEA.
	 * User: Michael Risher
	 * Date: 5/22/2017
	 * Time: 09:47
	 */
?>
<!DOCTYPE html>
<html>
<head>
	<?php
		include_once CORE_PATH . 'assets/inc/header.php';
		Core::includeStyles();
	?>
</head>
<body>
	<div id="wrapper">
		<div id="headerWrapper">
			<div id="header">
				<div class="clearfix">
					<div class="floatleft title">Computer Science <br> Computer Information Systems</div>
					<div class="floatleft subtitle">Join the Bitcoin Revolution.</div>
				</div>
			</div>
		</div>
		<div id="main">
			<div id="tree">
				<div class="aligncenter treemap">
					<img src="<?= CORE_URL . 'assets/img/tree.png'?>"/>
				</div>
				<div class="certList clearfix">
					<div class="floatleft">
						<div class="treeCertSubject">
							<p>Programming</p>
						</div>
						<table>
							<tr class="treeCertsDesc">
								<td>Description</td>
								<td>Cert #</td>
								<td>Units</td>
							</tr>
							<tr class="treeCert">
								<td><?=Core::fakeLink( 'cert','AS650','Computer Science AD-T')?></td>
								<td>AS650</td>
								<td>29</td>
							</tr>
							<tr class="treeCert">
								<td><?=Core::fakeLink( 'cert','CE728','Computer Programming')?></td>
								<td>AS/CE728</td>
								<td>26.5</td>
							</tr>
							<tr class="treeCert">
								<td><?=Core::fakeLink( 'cert','CE803','C++ Programming')?></td>
								<td>CE803</td>
								<td>13</td>
							</tr>
							<tr class="treeCert">
								<td><?=Core::fakeLink( 'cert','CE809','Java Programming')?></td>
								<td>CE809</td>
								<td>13</td>
							</tr>
							<tr class="treeCert">
								<td><?=Core::fakeLink( 'cert','CE816','Relational Database Technology')?></td>
								<td>CE816</td>
								<td>12</td>
							</tr>
							<tr class="treeCert">
								<td><?=Core::fakeLink( 'cert','CE806','Systems Development')?></td>
								<td>CE806</td>
								<td>12</td>
							</tr>
						</table>
					</div>
					<div class="middleTree floatleft">
						<div class="treeCertSubject">
							<p>Networking & Information Security</p>
						</div>
						<table>
							<tr class="treeCertsDesc">
								<td>Description</td>
								<td>Cert #</td>
								<td>Units</td>
							</tr>
							<tr class="treeCert">
								<td><?=Core::fakeLink( 'cert','CE810','CISCO Networking')?></td>
								<td>CE810</td>
								<td>16</td>
							</tr>
							<tr class="treeCert">
								<td><?=Core::fakeLink( 'cert','CEXXX','Information Security')?></td>
								<td>CEXXX</td>
								<td>17</td>
							</tr>
							<tr>
								<td class="aligncenter" colspan="3"><p class="padding15Top">Checkout <br> CSUSB CyberSecurity<br>Center</p></td>
							</tr>
						</table>
					</div>
					<div class="floatright">
						<div class="treeCertSubject">
							<p>Web Master & Applications</p>
						</div>
						<table>
							<tr class="treeCertsDesc">
								<td>Description</td>
								<td>Cert #</td>
								<td>Units</td>
							</tr>
							<tr class="treeCert">
								<td><?=Core::fakeLink( 'cert','CE843','Web Developer')?></td>
								<td>CE843</td>
								<td>17</td>
							</tr>
							<tr class="treeCert">
								<td><?=Core::fakeLink( 'cert','CE820','Web Designer')?></td>
								<td>CE820</td>
								<td>17</td>
							</tr>
							<tr class="treeCert">
								<td><?=Core::fakeLink( 'cert','CE276','Computer Applications')?></td>
								<td>AS/CE276</td>
								<td>32.5</td>
							</tr>
							<tr>
								<td class="aligncenter" colspan="3"><p class="padding15Top">* Not required but recommended</p></td>
							</tr>
						</table>
					</div>
				</div>
			</div>
			<div class="none" id="cert">
				<?php //include CORE_PATH . 'assets/inc/cert/CE803.php'; ?>
			</div>
			<div class="none" id="class">
				<?php //include CORE_PATH . 'assets/inc/class/c1a.php'; ?>
			</div>
		</div>
	</div>
	<?php include_once CORE_PATH . 'assets/inc/footer.php'; ?>
</body>
</html>

<!--
<tr class="treeCert">
	<td></td>
	<td></td>
	<td></td>
</tr>

-->