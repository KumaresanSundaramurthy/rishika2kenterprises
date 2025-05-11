<?php $this->load->view('common/header'); ?>

<?php $this->load->view('common/menu_view'); ?>

<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Create Grading Assignment Logic</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="/grading/assignment">Grading Assignment Logic</a></li>
                            <li class="breadcrumb-item active">Create Grading Assignment Logic</li>
                        </ol>
                    </div>

                </div>
            </div>
        </div>
        <!-- end page title -->

    <?php $FormAttribute = array('id' => 'AddGradingLogicForm', 'name' => 'AddGradingLogicForm', 'autocomplete' => 'off');
            echo form_open('/grading/assignment/add', $FormAttribute); ?>

        <div class="row">
            <div class="col-lg-8">

                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Basic Information</h4>
                    </div>
                    <div class="card-body">
                        
                        <div class="row mb-3">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label" for="LogicName">Logic Name <code>*</code></label>
                                    <input type="text" class="form-control" name="LogicName" id="LogicName" placeholder="Logic Name" required />
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label for="EnrollType" class="form-label">Course Type <code>*</code></label>
                                    <select class="form-select" name="EnrollType" id="EnrollType" required>
                                        <option value="">-- Select Course Type --</option>
                                        <option value="Live">Live Course</option>
                                        <option value="Recorded">Recorded Course</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="CourseID" class="form-label">Course Name <code>*</code></label>
                            <select class="form-select mb-3" name="CourseID" id="CourseID" required></select>
                        </div>

                        <div class="mb-3 d-none" id="ModuleDiv">
                            <label for="ModuleID" class="form-label">Module Name <code>*</code></label>
                            <select class="form-select mb-3" name="ModuleID" id="ModuleID"></select>
                        </div>

                        <div class="mb-3">
                            <label for="AssignmentType" class="form-label">Assignment Type <code>*</code></label>
                            <select class="form-select" name="AssignmentType" id="AssignmentType" required>
                                <option value="RoundRobin" selected>Round Robin</option>
                                <option value="Manual">Manual</option>
                            </select>
                        </div>

                    </div>
                    <!-- end card body -->
                </div>
                <!-- end card -->

                <div id="AddGradingLogicFormAlert" class="d-none col-lg-12 p-2" role="alert"></div>

                <div class="text-end mb-4">
                    <a href="javascript: history.back();" class="btn btn-danger me-1 w-sm">Cancel</a>
                    <button type="submit" id="AddGradingLogicSubmitBtn" class="btn btn-success w-sm">Save</button>
                </div>

            </div>
            <!-- end col -->

        </div>
        <!-- end row -->

    <?php echo form_close(); ?>

        <?php // $this->load->view('common/modals'); ?>

    </div>
    <!-- container-fluid -->
</div>
<!-- End Page-content -->

<?php $this->load->view('common/footer'); ?>