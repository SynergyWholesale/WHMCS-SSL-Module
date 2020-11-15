 <h2>Certificate Reissued</h2>
 <fieldset class="form-horizontal">
        <div class="form-group">
            <label class="col-sm-2 control-label">Status</label>
            <div class="col-sm-10">
                <p>{$status}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">SSL Certificate</label>
            <div class="col-sm-10">
                <textarea class="form-control" rows="12" cols="90" readonly=true style="width: 650px; height: 260px;">{$cer}</textarea>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">SSL Certificate for IIS</label>
            <div class="col-sm-10">
                <textarea class="form-control" rows="12" cols="90" readonly=true style="width: 650px; height: 260px;">{$p7b}</textarea>
            </div>
        </div>
       <div class="form-group">
            <div class="col-sm-12">
                <a href="clientarea.php?action=productdetails&id={$serviceid}" class="btn btn-primary">Go back</a>
            </div>
        </div>
 </fieldset>