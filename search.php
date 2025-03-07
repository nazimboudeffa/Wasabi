<?php
require_once '../../videos/configuration.php';
if (!User::isLogged()) {
    header("Location: {$global['webSiteRootURL']}?error=" . __("You can not do this"));
    exit;
}
$obj = YouPHPTubePlugin::getObjectData("Wasaaa");
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language']; ?>">
    <head>
        <script src="https://sdk.amazonaws.com/js/aws-sdk-2.558.0.min.js"></script>
        <title><?php echo $config->getWebSiteTitle(); ?>  :: Wasabi Embed</title>
        <?php
        include $global['systemRootPath'] . 'view/include/head.php';
        ?>
    </head>
    <body class="<?php echo $global['bodyClass']; ?>">
        <?php
        include $global['systemRootPath'] . 'view/include/navbar.php';
        ?>
        <div class="container">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <div id="select-input">
                        <div class="input-group col-md-12">
                          <!-- Select -->
                        </div>
                    </div>
                    <br>
                    <div class="row">
                      <div class="col-sm-6">
                          <button class="btn btn-success btn-block" id="getSelected"><?php echo __('Embed Selected'); ?></button>
                      </div>
                      <div class="col-sm-6">
                          <button class="btn btn-info btn-block" id="getAll"><?php echo __('Embed All'); ?></button>
                      </div>
                    </div>
                </div>
                <div class="panel-body">
                    <ul id="results" class="list-group"></ul>
                </div>
            </div>
        </div>
        <?php
        include $global['systemRootPath'] . 'view/include/footer.php';
        ?>
        <script>

            var accessKeyId = '<?php echo $obj->API_KEY; ?>';
            var secretAccessKey = '<?php echo $obj->API_SECRET; ?>';

            var wasabiEndpoint = new AWS.Endpoint('s3.wasabisys.com');
            var s3 = new AWS.S3({
                signatureVersion: 'v2',
                endpoint: wasabiEndpoint,
                accessKeyId: accessKeyId,
                secretAccessKey: secretAccessKey
            });

            var buck;
            var vids = [];

            var params = {};
            s3.listBuckets(params, function(err, data) {
             if (err) console.log(err, err.stack);
             else {

               var buckets = [];

               data.Buckets.forEach(function (element) {
                   buckets.push({
                       bucket: element.Name
                   });
               });

               var sel = $('<select>').appendTo('body');
                sel.append($("<option>").text("Select a bucket").prop('disabled', true).prop('selected', true));
               buckets.forEach(function(element) {
                sel.append($("<option>").attr('value',element.bucket).text(element.bucket));
               });
               $(sel).attr('id', "buckets");
               $(sel).addClass('form-control');
               $('#select-input').append(sel);
             }
            });

            $(document).ready(function () {
              $('#buckets').on('change', function(){

                modal.showPleaseWait();

                setTimeout(function () {
                  search($('#buckets option:selected').val());
                  modal.hidePleaseWait();
                },500);

              });
              $('#getSelected').click(function () {
                  var videoLink = new Array();
                  $("input:checkbox[name=videoCheckbox]:checked").each(function () {
                      videoLink.push($(this).val());
                  });
                  saveIt(videoLink);
              });
            });

            function saveIt(videoLink) {
              modal.showPleaseWait();
              setTimeout(function () {
                var objectsToSave = [];
                for (x in videoLink) {
                  if (typeof videoLink[x] === 'function') {
                              continue;
                          }
                  var o = {};

                  for (i=0; i<vids.length; i++){
                    if (vids[i].id == "myVideo-"+videoLink[x]){
                      o.duration = vids[i].duration;
                    }
                  }

                  o.title = videoLink[x];
                  o.link = 'https://s3.' + '<?php echo $obj->REGION; ?>' + '.wasabisys.com/' + buck + '/' + videoLink[x];
                  objectsToSave.push(o);
                }
                $.ajax({
                    url: '<?php echo $global['webSiteRootURL']; ?>plugin/Wasaaa/save.json.php',
                    data: {"objectsToSave": objectsToSave},
                    type: 'post',
                    success: function (response) {
                        if (!response.error) {
                            swal("<?php echo __("Congratulations!"); ?>", "<?php echo __("Your videos have been saved!"); ?>", "success");
                        } else {
                            swal("<?php echo __("Sorry!"); ?>", response.msg.join("<br>"), "error");
                        }
                        modal.hidePleaseWait();
                    }
                });
              },500);
            }

            function search(bucket){

              buck = bucket;

              var params = {
                  Bucket: bucket
              };

              var files = [];

              s3.listObjectsV2(params, function (err, data) {
                  if (!err) {
                      data.Contents.forEach(function (element) {
                          files.push({
                              filename: element.Key
                          });
                      });

                      $('#results').html('');
                      $.each(files, function (i, file) {
                          // Get Output
                          var output = getOutput(bucket, file);
                          // display results
                          $('#results').append(output);

                          // Get duration of video
                          var myVideoPlayer = document.getElementById("myVideo-"+file.filename);
                          myVideoPlayer.addEventListener('loadedmetadata', function () {
                              vids.push(myVideoPlayer);
                          });
                      });

                  } else {
                      console.log(err);  // an error ocurred
                  }
              });

            }

            function getOutput(b, f) {
              var title = f.filename;
              // Build output string
              var output = '<li class="list-group-item">' +
                      '<video id="myVideo-' + title + '" width="320" height="176" controls>' +
                        '<source src="https://s3.' + '<?php echo $obj->REGION; ?>' + '.wasabisys.com/' + b + '/' + title + '?rel=0" type="video/mp4">' +
                        'Your browser does not support HTML5 video.' +
                      '</video>' +
                      '<div class="checkbox">' +
                      '<label><input class="checkbox-inline" type="checkbox" value="' + title + '" name="videoCheckbox">' + title + '<a target="_blank" href="https://s3.' + '<?php echo $obj->REGION; ?>' + '.wasabisys.com/' + b + '/' + title + '?rel=0"> <i class="far fa-play-circle"></i></a></label>' +
                      '</div>' +
                      '</li>' +
                      '';
              return output;

            }

        </script>
    </body>
</html>
