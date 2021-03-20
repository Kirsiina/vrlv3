<?php

$pulju = false;
$nayttelyt = false;

if (isset($jaos['pulju']) && $jaos['pulju'] == true){
 $pulju = true;
}else if(isset($jaos['nayttelyt']) && $jaos['nayttelyt'] == 1){
 $nayttelyt = true;
}

        
        ?>
        
    
    
    <h2>Muokkaa jaosta <?=$jaos['nimi']?> (<?=$jaos['lyhenne']?>)</h2>


   <ul class="nav nav-tabs">
      <li role="presentation" class="<?php if ($sivu == 'tiedot'){echo "active";}?>"><a href="<?php echo base_url($url . 'tiedot')?>">Tiedot</a></li>
      <?php
       if (!($pulju || $nayttelyt) ){
        
        ?>
      
      <li role="presentation" class="<?php if ($sivu == 'saannot'){echo "active";}?>"><a href="<?php echo base_url($url . 'saannot')?>">Säännot</a></li>
      <li role="presentation" class="<?php if ($sivu == 'luokat'){echo "active";}?>"><a href="<?php echo base_url($url . 'luokat')?>">Luokat</a></li>
      <li role="presentation" class="<?php if ($sivu == 'ominaisuudet'){echo "active";}?>"><a href="<?php echo base_url($url . 'ominaisuudet')?>">Ominaisuudet</a></li>
      
      <?php
       } else if(!$pulju){
        
        ?>
              <li role="presentation" class="<?php if ($sivu == 'palkinnot'){echo "active";}?>"><a href="<?php echo base_url($url . 'palkinnot')?>">Palkinnot</a></li>
<?php
       } else {
        ?>
              <li role="presentation" class="<?php if ($sivu == 'rodut'){echo "active";}?>"><a href="<?php echo base_url($url . 'rodut')?>">Rodut</a></li>
<?php
       }
       
       ?>
      <li role="presentation" class="<?php if ($sivu == 'omistajat'){echo "active";}?>"><a href="<?php echo base_url($url . 'omistajat')?>">Ylläpito</a></li>
      <li role="presentation" class="<?php if ($sivu == 'online'){echo "active";}?>"><a href="<?php echo base_url($url . 'online')?>">Toiminnassa</a></li>
    </ul>

   
   <?php


   
       if (isset($msg)){
         echo '<div class="alert alert-'. fuel_var('msg_type', 'info') . '" role="alert">';
         echo fuel_var('msg', '');
         echo validation_errors();
         echo '</div>';
         
         
       }
       
       
       echo fuel_var('info', '');
       
       if($sivu == 'luokat'){
         echo '<p>Tältä sivulta voit hallita jaoksesi luokkia. Järjestysnumerolla voit valita, missä järjestyksessä luokat esitetään sääntölistauksissa (pienin ylimpänä). Huom! Luokkia, joilla on järjestetty jo kilpailuja, ei voi poistaa.
         Ne voi kuitenkin merkitä pois käytöstä, jolloin ne eivät näy sääntölistauksissa eikä niitä voi valita kilpailuihin.</p>';
         
         echo '<a href="'.base_url($url . $sivu . "/lisaa") . '">Lisää uusi luokka</a>';
       }
       
      else if($sivu == 'ominaisuudet'){
         echo '<p>Tältä sivuilta voit valita mitkä ominaisuudet vaikuttavat jaoksesi porrastetuissa kilpailuissa!
         Ominaisuuksia voi valita 2-4. Voit muokata tätä vain, jos jaoksen porrastetut kilpailut eivät ole vielä asetettu toimintaan.</p>
         
         <p>Kun valitset ominaisuuksia, tarkastathan että muilla jaoksilla ei ole käytössä liian samankaltaista ominaisuuskombinaatiota.
         Nyrkkisääntö: Laske jokaisen samankaltaisen jaoksen kohdalla (yhteisten ominaisuuksien määrä) / (tässä valitsemiesi ominaisuuksien määrä).
         Luvun tulee olla alle 0,75 mutta mieluiten vähemmän, jotta esimerkiksi laatuarvostelupisteiden kohdalla jaokset eivät haittaa toisiaan.</p>';
                }
                
      else if($sivu == 'omistajat'){
         if($pulju){
           echo '<p>Yhdistyksellä voi olla useita ylläpitäjiä (taso 1) ja muita työntekijöitä (taso 0).
      Ylläpitäjä pystyy muokkaamaan ylläpitäjiä ja työntekijöitä ja hallitsemaan tapahtumia. Muilla työntekijöillä voi olla yhdistyksestä riippuen eri oikeuksia,
      esim. rotuveriprosenttien kirjaus hevosrekisteriin.</p>';
          }
          else {
           echo '<p>Jaoksella voi olla useita ylläpitäjiä (taso 1) ja kalenterityöntekijöitä (taso 0).
           Ylläpitäjä pystyy muokkaamaan jaoksen sääntöjä, ylläpitäjiä ja työntekijöitä. Kalenterityöntekijällä on oikeus hyväksyä ja muokata kilpailuja ja tuloksia.</p>';
           }
      }
      
      else if($sivu == 'palkinnot'){
         
      echo '<p>Tällä sivulla voit määritellä mitä palkintoja jaoksesi näyttelyistä voi saada. Vain tänne listatut palkinnot tulevat tulosten
      lähetyksen myötä näkyviin hevosten profiileissa.</p>';
      
       echo '<a href="'.base_url($url . $sivu . "/lisaa") . '">Lisää uusi palkinto</a>';

      }
       
       echo fuel_var('form', '');
       echo fuel_var('list', ''); 

 ?>