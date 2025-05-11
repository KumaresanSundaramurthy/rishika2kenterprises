<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/header'); ?>

<?php $this->load->view('common/menu_view'); ?>

<div class="container-fluid" style="padding-top: 90px; padding-bottom: 25px; width: 97%">
	<div class="row" style='margin-top: -25px;'>
		<div class="col-lg-4 col-sm-4 col-md-4 col-xs-12">
			<div class="col-lg-12 col-sm-12 col-md-12 col-xs-12">
				<a href="<?php  echo base_url();  ?>index.php/RptDues_Controller" target="blank"><h4>Dues List</h4></a>
				<div id="divTable" class="divTable col-lg-12 col-md-12 col-sm-12 col-xs-12" style="border:1px solid lightgray; padding: 10px;height:250px; overflow:auto;">
					<table class='table table-hover table-condensed table-striped table-bordered' id='tblDues'>
					 <thead>
						 <tr>
							<th style='display:none;'>customerRowid</th>
						 	<th style='display:none1;'>Name</th>
						 	<th style='display:none1;'>Dues</th>
						 	<th style='display:none;'>Receive</th>
						 	<th style='display:none;'>Remarks</th>
						 	<th style='display:none;'></th>
						 	<th style='display:none;'>Mobile</th>
						 </tr>
					 </thead>
					 <tbody>
                        
					 </tbody>
					</table>
				</div>
			</div>
		</div>

		<div class="col-lg-4 col-sm-4 col-md-4 col-xs-12">
			<div class="col-lg-12 col-sm-12 col-md-12 col-xs-12">
				<a href="<?php  echo base_url();  ?>index.php/Reminders_Controller" target="blank"><h4>Notifications/Reminders</h4></a>
				<div id="divTable" class="divTable col-lg-12 col-md-12 col-sm-12 col-xs-12" style="border:1px solid lightgray; padding: 10px;height:250px; overflow:auto;">
					<table class='table table-hover table-condensed table-striped table-bordered' id='tblReminders'>
					 <thead>
						 <tr>
							<th style='display:none;'>Rowid</th>
						 	<th style='display:none1;'>Date</th>
						 	<th style='display:none1;'>Reminder</th>
						 	<th style='display:none1;'>Repeat</th>
						 	<th style='display:none1;'>Type</th>
						 </tr>
					 </thead>
					 <tbody>
                        
					 </tbody>
					</table>
				</div>
			</div>
		</div>

		<div class="col-lg-4 col-sm-4 col-md-4 col-xs-12">
			<div class="col-lg-12 col-sm-12 col-md-12 col-xs-12">
				<a href="<?php  echo base_url();  ?>index.php/Complaint_Controller" target="blank"><h4>Complaints</h4></a>
				<div id="divTable" class="divTable col-lg-12 col-md-12 col-sm-12 col-xs-12" style="border:1px solid lightgray; padding: 10px;height:250px; overflow:auto;">
					<table class='table table-hover table-condensed table-striped table-bordered' id='tblComplaints'>
					 <thead>
						 <tr>
							<th>#</th>
						 	<th>Date</th>
						 	<th style='display:none;'>customerRowId</th>
						 	<th>Customer</th>
						 	<th>Complaint</th>
						 	<th>Solved</th>
						 	<th>Address</th>
						 	<th>Contact</th>
						 </tr>
					 </thead>
					 <tbody>

					 </tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
<hr/>
	<div class="row" style='margin-top: -1px;'>
		<div class="col-lg-6 col-sm-6 col-md-6 col-xs-12">
			<div class="col-lg-12 col-sm-12 col-md-12 col-xs-12">
				<a href="<?php  echo base_url();  ?>index.php/Replacement_Controller" target="blank"><h4>Replacements</h4></a>
				<div id="divTable" class="divTable col-lg-12 col-md-12 col-sm-12 col-xs-12" style="border:1px solid lightgray; padding: 10px;height:250px; overflow:auto;">
					<table class='table table-hover table-condensed table-striped table-bordered' id='tblReplacements'>
					 <thead>
						 <tr>
							<th style='display:none;'>rowid</th>
						 	<th>Date</th>
						 	<th style='display:none;'>ItemRowId</th>
						 	<th>Item</th>
						 	<th style='display:none;'>PartyRowId</th>
						 	<th>Party</th>
						 	<th>Qty</th>
						 	<th>Remarks</th>
						 	<th>Sent</th>
						 	<th>SentDt</th>
						 	<th>Recd</th>
						 	
						 </tr>
					 </thead>
					 <tbody>

					 </tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="col-lg-6 col-sm-6 col-md-6 col-xs-12">
			<div class="col-lg-12 col-sm-12 col-md-12 col-xs-12">
				<a href="<?php  echo base_url();  ?>index.php/Requirement_Controller" target="blank"><h4>Requirements</h4></a>
				<div id="divTable" class="divTable col-lg-12 col-md-12 col-sm-12 col-xs-12" style="border:1px solid lightgray; padding: 10px;height:250px; overflow:auto;">
					<table class='table table-hover table-condensed table-striped table-bordered' id='tblRequirements'>
					 <thead>
						 <tr>
							<th style='width:0px;display:none;'>rowid</th>
						 	<th style="display: none;">ItemRowId</th>
						 	<th>Item Name</th>
						 	<th>Last Rate (Per Pc.)</th>
						 	<th>From</th>
						 	<th>Date</th>
						 </tr>
					 </thead>
					 <tbody>

					 </tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<?php $this->load->view('common/footer'); ?>