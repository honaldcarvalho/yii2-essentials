<?php

$icon = 'fa-folder';
$url = "/folder";
$type = "folder";

$html = <<< HTML

    <div class="col-lg-3 col-md-4 col-sm-12">

        <div class="card">
            <div id="overlay-{$model->id}" class="overlay" style="height: 100%;position: absolute;width: 100%;z-index: 3000;display:none;top:0;left:0;">
                <div class="fa-3x">
                    <i class="fas fa-sync fa-spin"></i>
                </div>
            </div>

            <div class="file">
                <div class="link-file">
                    <div class="hover">
                        
                        <a href="{$url}/edit/{$model->id}"
                        data-id="{$model->id}" 
                        data-fancybox
                        data-type="iframe"
                        data-custom-class="iframe-{$model->id}"
                        class="btn btn-icon btn-warning">
                            <i class="fa fa-edit"></i>
                        </a>

                        <a onclick='removeFile($(this))' 
                            class="btn btn-icon btn-danger"
                            data-id="{$model->id}"
                            data-link="{$url}/remove/{$model->id}"
                        >
                            <i class="fa fa-trash"></i>
                        </a>
                    </div>

                    <a class="icon" href="{$url}/{$model->id}">
                        <i class="fa {$icon} text-warning"></i>
                    </a>
                    
                    <div class="file-name">
                        <p class="m-b-5 text-muted">{$model->name}</p>
                        <small>Size: 42KB <span class="date text-muted">Nov 02, 2017</span></small>
                    </div>
</div>
            </div>
        </div>
    </div>
HTML;

echo $html;
