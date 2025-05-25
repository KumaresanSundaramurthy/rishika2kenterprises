<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/header'); ?>

<!-- Layout wrapper -->
<div class="layout-wrapper layout-content-navbar">
	<div class="layout-container">

		<?php $this->load->view('common/menu_view'); ?>

		<!-- Layout container -->
		<div class="layout-page">

			<?php $this->load->view('common/navbar_view'); ?>

			<!-- Content wrapper -->
			<div class="content-wrapper">
				
				<div class="container-xxl flex-grow-1 container-p-y">
					<div class="row">

						<div class="col-lg-4 col-sm-4 col-md-4 col-xs-12">
							<!-- Striped Rows -->
							<div class="card">
								<h5 class="card-header">Striped Rows</h5>
								<div class="table-responsive text-nowrap">
									<table class="table table-striped">
										<thead>
											<tr>
												<th>Project</th>
												<th>Client</th>
												<th>Users</th>
												<th>Status</th>
												<th>Actions</th>
											</tr>
										</thead>
										<tbody class="table-border-bottom-0">
											<tr>
												<td><i class="fab fa-angular fa-lg text-danger me-3"></i> <strong>Angular Project</strong></td>
												<td>Albert Cook</td>
												<td>
													<ul class="list-unstyled users-list m-0 avatar-group d-flex align-items-center">
														<li
															data-bs-toggle="tooltip"
															data-popup="tooltip-custom"
															data-bs-placement="top"
															class="avatar avatar-xs pull-up"
															title="Lilian Fuller">
															<img src="../assets/img/avatars/5.png" alt="Avatar" class="rounded-circle" />
														</li>
														<li
															data-bs-toggle="tooltip"
															data-popup="tooltip-custom"
															data-bs-placement="top"
															class="avatar avatar-xs pull-up"
															title="Sophia Wilkerson">
															<img src="../assets/img/avatars/6.png" alt="Avatar" class="rounded-circle" />
														</li>
														<li
															data-bs-toggle="tooltip"
															data-popup="tooltip-custom"
															data-bs-placement="top"
															class="avatar avatar-xs pull-up"
															title="Christina Parker">
															<img src="../assets/img/avatars/7.png" alt="Avatar" class="rounded-circle" />
														</li>
													</ul>
												</td>
												<td><span class="badge bg-label-primary me-1">Active</span></td>
												<td>
													<div class="dropdown">
														<button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
															<i class="bx bx-dots-vertical-rounded"></i>
														</button>
														<div class="dropdown-menu">
															<a class="dropdown-item" href="javascript:void(0);"><i class="bx bx-edit-alt me-1"></i> Edit</a>
															<a class="dropdown-item" href="javascript:void(0);"><i class="bx bx-trash me-1"></i> Delete</a>
														</div>
													</div>
												</td>
											</tr>
											<tr>
												<td><i class="fab fa-react fa-lg text-info me-3"></i> <strong>React Project</strong></td>
												<td>Barry Hunter</td>
												<td>
													<ul class="list-unstyled users-list m-0 avatar-group d-flex align-items-center">
														<li
															data-bs-toggle="tooltip"
															data-popup="tooltip-custom"
															data-bs-placement="top"
															class="avatar avatar-xs pull-up"
															title="Lilian Fuller">
															<img src="../assets/img/avatars/5.png" alt="Avatar" class="rounded-circle" />
														</li>
														<li
															data-bs-toggle="tooltip"
															data-popup="tooltip-custom"
															data-bs-placement="top"
															class="avatar avatar-xs pull-up"
															title="Sophia Wilkerson">
															<img src="../assets/img/avatars/6.png" alt="Avatar" class="rounded-circle" />
														</li>
														<li
															data-bs-toggle="tooltip"
															data-popup="tooltip-custom"
															data-bs-placement="top"
															class="avatar avatar-xs pull-up"
															title="Christina Parker">
															<img src="../assets/img/avatars/7.png" alt="Avatar" class="rounded-circle" />
														</li>
													</ul>
												</td>
												<td><span class="badge bg-label-success me-1">Completed</span></td>
												<td>
													<div class="dropdown">
														<button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
															<i class="bx bx-dots-vertical-rounded"></i>
														</button>
														<div class="dropdown-menu">
															<a class="dropdown-item" href="javascript:void(0);"><i class="bx bx-edit-alt me-1"></i> Edit</a>
															<a class="dropdown-item" href="javascript:void(0);"><i class="bx bx-trash me-1"></i> Delete</a>
														</div>
													</div>
												</td>
											</tr>
											<tr>
												<td><i class="fab fa-vuejs fa-lg text-success me-3"></i> <strong>VueJs Project</strong></td>
												<td>Trevor Baker</td>
												<td>
													<ul class="list-unstyled users-list m-0 avatar-group d-flex align-items-center">
														<li
															data-bs-toggle="tooltip"
															data-popup="tooltip-custom"
															data-bs-placement="top"
															class="avatar avatar-xs pull-up"
															title="Lilian Fuller">
															<img src="../assets/img/avatars/5.png" alt="Avatar" class="rounded-circle" />
														</li>
														<li
															data-bs-toggle="tooltip"
															data-popup="tooltip-custom"
															data-bs-placement="top"
															class="avatar avatar-xs pull-up"
															title="Sophia Wilkerson">
															<img src="../assets/img/avatars/6.png" alt="Avatar" class="rounded-circle" />
														</li>
														<li
															data-bs-toggle="tooltip"
															data-popup="tooltip-custom"
															data-bs-placement="top"
															class="avatar avatar-xs pull-up"
															title="Christina Parker">
															<img src="../assets/img/avatars/7.png" alt="Avatar" class="rounded-circle" />
														</li>
													</ul>
												</td>
												<td><span class="badge bg-label-info me-1">Scheduled</span></td>
												<td>
													<div class="dropdown">
														<button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
															<i class="bx bx-dots-vertical-rounded"></i>
														</button>
														<div class="dropdown-menu">
															<a class="dropdown-item" href="javascript:void(0);"><i class="bx bx-edit-alt me-1"></i> Edit</a>
															<a class="dropdown-item" href="javascript:void(0);"><i class="bx bx-trash me-1"></i> Delete</a>
														</div>
													</div>
												</td>
											</tr>
											<tr>
												<td>
													<i class="fab fa-bootstrap fa-lg text-primary me-3"></i> <strong>Bootstrap Project</strong>
												</td>
												<td>Jerry Milton</td>
												<td>
													<ul class="list-unstyled users-list m-0 avatar-group d-flex align-items-center">
														<li
															data-bs-toggle="tooltip"
															data-popup="tooltip-custom"
															data-bs-placement="top"
															class="avatar avatar-xs pull-up"
															title="Lilian Fuller">
															<img src="../assets/img/avatars/5.png" alt="Avatar" class="rounded-circle" />
														</li>
														<li
															data-bs-toggle="tooltip"
															data-popup="tooltip-custom"
															data-bs-placement="top"
															class="avatar avatar-xs pull-up"
															title="Sophia Wilkerson">
															<img src="../assets/img/avatars/6.png" alt="Avatar" class="rounded-circle" />
														</li>
														<li
															data-bs-toggle="tooltip"
															data-popup="tooltip-custom"
															data-bs-placement="top"
															class="avatar avatar-xs pull-up"
															title="Christina Parker">
															<img src="../assets/img/avatars/7.png" alt="Avatar" class="rounded-circle" />
														</li>
													</ul>
												</td>
												<td><span class="badge bg-label-warning me-1">Pending</span></td>
												<td>
													<div class="dropdown">
														<button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
															<i class="bx bx-dots-vertical-rounded"></i>
														</button>
														<div class="dropdown-menu">
															<a class="dropdown-item" href="javascript:void(0);"><i class="bx bx-edit-alt me-1"></i> Edit</a>
															<a class="dropdown-item" href="javascript:void(0);"><i class="bx bx-trash me-1"></i> Delete</a>
														</div>
													</div>
												</td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
							<!--/ Striped Rows -->
						</div>

					</div>
				</div>

			</div>
			<!-- Content wrapper -->

			<div class="container-fluid" style="padding-top: 90px; padding-bottom: 25px; width: 97%">
				<div class="row" style='margin-top: -25px;'>
					<div class="col-lg-4 col-sm-4 col-md-4 col-xs-12">
						<div class="col-lg-12 col-sm-12 col-md-12 col-xs-12">
							<a href="<?php echo base_url();  ?>index.php/RptDues_Controller" target="blank">
								<h4>Dues List</h4>
							</a>
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
							<a href="<?php echo base_url();  ?>index.php/Reminders_Controller" target="blank">
								<h4>Notifications/Reminders</h4>
							</a>
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
							<a href="<?php echo base_url();  ?>index.php/Complaint_Controller" target="blank">
								<h4>Complaints</h4>
							</a>
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
				<hr />
				<div class="row" style='margin-top: -1px;'>
					<div class="col-lg-6 col-sm-6 col-md-6 col-xs-12">
						<div class="col-lg-12 col-sm-12 col-md-12 col-xs-12">
							<a href="<?php echo base_url();  ?>index.php/Replacement_Controller" target="blank">
								<h4>Replacements</h4>
							</a>
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
							<a href="<?php echo base_url();  ?>index.php/Requirement_Controller" target="blank">
								<h4>Requirements</h4>
							</a>
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

			<?php $this->load->view('common/footer_desc'); ?>

		</div>

	</div>
</div>

<?php $this->load->view('common/footer'); ?>