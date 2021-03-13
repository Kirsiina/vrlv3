<h2>Muokkaa hevosta <?=$hevonen['nimi']?> (<?=$hevonen['reknro']?>)</h2>

<?php if(strlen(fuel_var('msg', '')) > 0){ ?>
    <div class="alert alert-<?php echo fuel_var('msg_type', 'info')?>" role="alert">   
        <?php echo fuel_var('msg', '')?>
        <?php echo validation_errors(); ?>
    </div>
    
    <?php } ?>


   <ul class="nav nav-tabs">
        <li role="presentation" class="<?php if ($sivu == 'tiedot'){echo "active";}?>"><a href="<?php echo base_url('virtuaalihevoset/muokkaa/'. $hevonen['reknro'] . '/tiedot')?>">Tiedot</a></li>
        <li role="presentation" class="<?php if ($sivu == 'omistajat'){echo "active";}?>"><a href="<?php echo base_url('virtuaalihevoset/muokkaa/'. $hevonen['reknro'] . '/omistajat')?>">Omistajat</a></li>
        <li role="presentation" class="<?php if ($sivu == 'varsat'){echo "active";}?>"><a href="<?php echo base_url('virtuaalihevoset/muokkaa/'. $hevonen['reknro'] . '/varsat')?>">Jälkeläiset</a></li>

    </ul>
       <?php echo fuel_var('info', '');?>

   
     <?php
    if($sivu == 'tiedot'){     
        echo fuel_var('editor', '');
        }
        
   else if($sivu == 'varsat'){
      echo $form;
        echo $foals;
   }

    else if($sivu == 'omistajat'){?>
          <div class="panel panel-default"><div class="panel-body">
            <p>Hevosella voi olla useita omistajia (taso 1) ja haltijoita (taso 0).
            Omistaja pystyy muokkaamaan hevosen muita omistajia, haltija pystyy muokkaamaan tietoja,
            mutta ei voi muokata omistajia muilta kuin omalta osaltaan. Hevosella pitää olla vähintään yksi omistaja.</p>
            <?php if(!isset($form)){
               echo '<p><a href="'.base_url().$delete_owner_url.'">Poista itsesi omistajista.</a></p>';
            } else {
               echo $form;
            }
?>             </div></div>
            <?php echo fuel_var('ownership', '');?>

            
            
        <?php }
        
        ?>