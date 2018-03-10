<?php
	session_start ();    
	
	$type = $_POST['type'];  
	$probeset_id = $_POST['probeset_id'];
	$genesymbol = $_POST['genesymbol'];
	$datagroup = $_POST['datagroup'];
	$population = $_POST['population'];
	$pop_info=explode(",", $population);
	$key_population = $_POST['key_population'];
	
	$host = 'mysql.cl.med.harvard.edu';
	$user = 'rsimmgenweb';
	$pass = 'OilnemRaGrighViukap3';
	$db = 'rstatsimmgen';
	$immgen_data_group = 'ImmGen_data_group';
	$immgen_data_group_global = 'ImmGen_data_group_maximum';
	$key_pop_info = array('LTHSC_34-_BM','MMP2_150+48+_BM','proB_CLP_BM','proB_FrBC_BM','B_Fo_Sp','B_MZ_Sp','B_GC_CC_Sp','B_PC_Sp','B1b_PC','preT_DN1_Th','T_DP_Th','T_4_Nve_Sp','NKT_Sp','Treg_4_25hi_Sp','T_8_Nve_Sp','T8_TE_LCMV_d7_Sp','T8_Tem_LCMV_d180_Sp','Tgd_g2+d1_24a+_Th','Tgd_g2+d17_LN','Tgd_Sp','NK_27-11b+_Sp','ILC2_SI','ILC3_NKp46-CCR6-_SI','ILC3_NKp46+_SI','DC_8+_Sp','DC_pDC_Sp','GN_BM','GN_Thio_PC','MC_heparinase_PC','MF_PC','MF_Alv_Lu','Ep_MEChi_Th','LEC_SLN','IAP_SLN');
	
	if($datagroup == 'IFN'){
		$microarray_table = 'IFN_Network_class_mean';	
	}
	else {
		$microarray_table = 'ImmGen_class_mean';
	}
	//$rnaseq_table = 'IFN_Network_Maximum';
	
	$con = mysql_connect($host, $user, $pass) or die("Can not connect." . mysql_error());
	$db_selected = mysql_select_db($db,$con);
	if($type=='microarray'){
		$microarray_meta_table = 'ImmGen_RNAseq_meta_info';
		$microarray_kegg_table = 'ImmGen_RNAseq_meta_info_Entrez2KEGG';
		$microarray_orthology_table = 'ImmGen_RNAseq_meta_info_Human_Ortholog';
		$microarray_go_table = 'ImmGen_RNAseq_meta_info_GO';
		
		$microarray_population_query = "SELECT population, color, short_name, long_name, description, author, number_of_sample, sorting_info FROM ".$immgen_data_group." WHERE data_group = '".$datagroup."' ORDER BY `order`";
		$microarray_expr_query = "SELECT * FROM ".$microarray_table." WHERE ProbeSet_ID = '".$probeset_id."'";
		$microarray_expr_global_query = "SELECT population, value FROM ".$immgen_data_group_global." WHERE data_group = '".$datagroup."'";
		$microarray_column_query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$microarray_table."'";
		$microarray_meta_query = "SELECT * FROM ".$microarray_meta_table." WHERE Gene_Symbol = '".$genesymbol."'";
		$microarray_kegg_query = "SELECT k.* FROM ".$microarray_kegg_table." AS k, ".$microarray_meta_table." AS m WHERE m.Gene_Symbol = '".$genesymbol."' AND m.Entrez_Gene_ID = k.Entrez_Gene_ID";
		$microarray_go_query = "SELECT distinct(g.GO_ID), g.Category, g.GO_Name FROM ".$microarray_go_table." AS g, ".$microarray_meta_table." AS m WHERE m.Gene_Symbol = '".$genesymbol."' AND m.MGI_ID = g.MGI_ID ORDER BY g.Category DESC, g.GO_ID ASC";
		$microarray_orthology_query = "SELECT o.* FROM ".$microarray_orthology_table." AS o, ".$microarray_meta_table." AS m WHERE m.Gene_Symbol = '".$genesymbol."' AND m.MGI_ID = o.MGI_ID";
		
		$microarray_population_result = mysql_query($microarray_population_query,$con);
		$microarray_expr_result = mysql_query($microarray_expr_query,$con);
		$microarray_expr_global_result = mysql_query($microarray_expr_global_query,$con);
		$microarray_column_result = mysql_query($microarray_column_query,$con);
		$microarray_meta_result = mysql_query($microarray_meta_query,$con);
		$microarray_kegg_result = mysql_query($microarray_kegg_query,$con);
		$microarray_go_result = mysql_query($microarray_go_query,$con);
		$microarray_orthology_result = mysql_query($microarray_orthology_query,$con);
				
		$column_index = array();
		$index = 0;
		while ($r1 = mysql_fetch_row($microarray_column_result)) {
			$column_index[$r1[0]] = $index;
			$index = $index + 1;
		}
		
		$microarray_expr = mysql_fetch_array($microarray_expr_result);
		
		//$max = array();
		$microarray_max = 0;
		while ($r2 = mysql_fetch_row($microarray_expr_global_result)) {
			//$max[$r2[0]] = floatval($r2[1]);
			if(floatval($r2[1]) > $microarray_max){
				$microarray_max = floatval($r2[1]);	
			}
		}
		
		$microarray_meta = mysql_fetch_assoc($microarray_meta_result);
		
		$microarray_kegg = array();
		while ($r3 = mysql_fetch_row($microarray_kegg_result)) {
			array_push($microarray_kegg, array('kegg_id' => $r3[1], 'kegg_name' => $r3[2]));	
		}
		
		$microarray_go = array();
		while ($r4 = mysql_fetch_row($microarray_go_result)) {
			array_push($microarray_go, array('go_id' => $r4[0], 'go_category' => $r4[1], 'go_name' => $r4[2]));	
		}
		
		$microarray_orthology = mysql_fetch_assoc($microarray_orthology_result);
		
		$json = array();
		while ($r = mysql_fetch_row($microarray_population_result)) {
			array_push($json, array('population' => $r[0], 'start' => 0, 'value' => floatval($microarray_expr[$column_index[$r[0]]]), 'color' => $r[1], 'short_name' => $r[2], 'long_name' => $r[3], 'description' => $r[4], 'author' => $r[5], 'number_of_sample' => $r[6], 'sorting_info' => $r[7]));	
			//array_push($json, array('population' => $r[0], 'start' => 0, 'value' => floatval($expr[$column_index[$r[0]]]), 'color' => $r[1], 'short_name' => $r[2], 'long_name' => $r[3], 'description' => $r[4], 'author' => $r[5], 'number_of_sample' => $r[6], 'sorting_info' => $r[7], 'type' => 'data'));	
			//array_push($json, array('population' => $r[0], 'start' => floatval($expr[$column_index[$r[0]]]), 'value' => $max[$r[0]], 'color' => '#CCCCCC', 'short_name' => $r[2], 'long_name' => $r[3], 'description' => $r[4], 'author' => $r[5], 'number_of_sample' => $r[6], 'sorting_info' => $r[7], 'type' => 'max'));	
		}
		$output = array('meta'=>$microarray_meta, 'kegg'=>$microarray_kegg, 'go'=>$microarray_go, 'orthology'=>$microarray_orthology, 'data'=>$json, 'max'=>$microarray_max);
		echo json_encode($output); 
		
	}
	else if ($type=='rnaseq'){
		
		if($datagroup == 'ImmGen ULI RNASeq'){
			$rnaseq_table = 'ImmGen_ATAC_ULI_RNAseq_expr';
			
			if($key_population == 'Key populations'){
				$rnaseq_population_query = "SELECT population, color, short_name, long_name, description, author, number_of_sample, sorting_info, color_unique_set FROM ".$immgen_data_group." WHERE data_group = '".$datagroup."' AND population IN ('".implode("','",$key_pop_info)."') ORDER BY `order`";
				$rnaseq_population_group_query = "SELECT count(distinct(`population_group`)) FROM ".$immgen_data_group." WHERE data_group = '".$datagroup."' AND population IN ('".implode("','",$key_pop_info)."') ORDER BY `order`";
			}
			else{
				$rnaseq_population_query = "SELECT population, color, short_name, long_name, description, author, number_of_sample, sorting_info, color_unique_set FROM ".$immgen_data_group." WHERE data_group = '".$datagroup."' AND population IN ('".implode("','",$pop_info)."') ORDER BY `order`";
				$rnaseq_population_group_query = "SELECT count(distinct(`population_group`)) FROM ".$immgen_data_group." WHERE data_group = '".$datagroup."' AND population IN ('".implode("','",$pop_info)."') ORDER BY `order`";
			}
		}
		else if($datagroup == 'Male/Female RNASeq'){
			$rnaseq_table = 'ImmGen_RNAseq_expr';
			$rnaseq_population_query = "SELECT population, color, short_name, long_name, description, author, number_of_sample, sorting_info FROM ".$immgen_data_group." WHERE data_group = '".$datagroup."' ORDER BY `order`";
		}
		$rnaseq_meta_table = 'ImmGen_RNAseq_meta_info';
		$rnaseq_kegg_table = 'ImmGen_RNAseq_meta_info_Entrez2KEGG';
		$rnaseq_orthology_table = 'ImmGen_RNAseq_meta_info_Human_Ortholog';		
		$rnaseq_go_table = 'ImmGen_RNAseq_meta_info_GO';
		
		$rnaseq_expr_query = "SELECT * FROM ".$rnaseq_table." AS r, ".$rnaseq_meta_table." AS m WHERE m.Gene_Symbol_MGI = '".$probeset_id."' AND m.Gene_Symbol = r.Gene_Symbol";
		$rnaseq_expr_global_query = "SELECT population, value FROM ".$immgen_data_group_global." WHERE data_group = '".$datagroup."'";
		$rnaseq_column_query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$rnaseq_table."'";
		$rnaseq_meta_query = "SELECT * FROM ".$rnaseq_meta_table." WHERE Gene_Symbol_MGI = '".$probeset_id."'";
		$rnaseq_kegg_query = "SELECT k.* FROM ".$rnaseq_kegg_table." AS k, ".$rnaseq_meta_table." AS m WHERE m.Gene_Symbol_MGI = '".$probeset_id."' AND m.Entrez_Gene_ID = k.Entrez_Gene_ID";
		$rnaseq_go_query = "SELECT distinct(g.GO_ID), g.Category, g.GO_Name FROM ".$rnaseq_go_table." AS g, ".$rnaseq_meta_table." AS m WHERE m.Gene_Symbol_MGI = '".$probeset_id."' AND m.MGI_ID = g.MGI_ID ORDER BY g.Category DESC, g.GO_ID ASC";
		$rnaseq_orthology_query = "SELECT o.* FROM ".$rnaseq_orthology_table." AS o, ".$rnaseq_meta_table." AS m WHERE m.Gene_Symbol_MGI = '".$probeset_id."' AND m.MGI_ID = o.MGI_ID";
		
		$rnaseq_population_result = mysql_query($rnaseq_population_query,$con);
		$rnaseq_population_group_result = mysql_query($rnaseq_population_group_query,$con);
		$rnaseq_expr_result = mysql_query($rnaseq_expr_query,$con);
		$rnaseq_expr_global_result = mysql_query($rnaseq_expr_global_query,$con);
		$rnaseq_column_result = mysql_query($rnaseq_column_query,$con);
		$rnaseq_meta_result = mysql_query($rnaseq_meta_query,$con);
		$rnaseq_kegg_result = mysql_query($rnaseq_kegg_query,$con);
		$rnaseq_go_result = mysql_query($rnaseq_go_query,$con);
		$rnaseq_orthology_result = mysql_query($rnaseq_orthology_query,$con);
		
		$rnaseq_column_index = array();
		$rnaseq_index = 0;
		while ($r1 = mysql_fetch_row($rnaseq_column_result)) {
			$rnaseq_column_index[$r1[0]] = $rnaseq_index;
			$rnaseq_index = $rnaseq_index + 1;
		}
		
		$rnaseq_expr = mysql_fetch_array($rnaseq_expr_result);
		
		//$rnaseq_max = array();
		$rnaseq_max = 0;
		while ($r2 = mysql_fetch_row($rnaseq_expr_global_result)) {
			//$rnaseq_max[$r2[0]] = floatval($r2[1]);
			if(floatval($r2[1]) > $rnaseq_max){
				$rnaseq_max = floatval($r2[1]);	
			}
		}
		
		$rnaseq_meta = mysql_fetch_assoc($rnaseq_meta_result);
		
		$kegg = array();
		while ($r3 = mysql_fetch_row($rnaseq_kegg_result)) {
			array_push($kegg, array('kegg_id' => $r3[1], 'kegg_name' => $r3[2]));	
		}
		
		$go = array();
		while ($r4 = mysql_fetch_row($rnaseq_go_result)) {
			array_push($go, array('go_id' => $r4[0], 'go_category' => $r4[1], 'go_name' => $r4[2]));	
		}
		
		//$orthology = array();
		//while ($r4 = mysql_fetch_row($rnaseq_orthology_result)) {
		//	array_push($orthology, array('human_genesymbol' => $r4[0], 'human_entrezid' => $r4[1], 'summary' => $r4[3]));	
		//}
		
		$orthology = mysql_fetch_assoc($rnaseq_orthology_result);
		
		$r5 = mysql_fetch_row($rnaseq_population_group_result);
		$ranseq_population_group_num = $r5[0];
		
		$json = array();
		while ($r = mysql_fetch_row($rnaseq_population_result)) {
			array_push($json, array('population' => $r[0], 'start' => 0, 'value' => floatval($rnaseq_expr[$rnaseq_column_index[$r[0]]]), 'color' => $r[1], 'short_name' => $r[2], 'long_name' => $r[3], 'description' => $r[4], 'author' => $r[5], 'number_of_sample' => $r[6], 'sorting_info' => $r[7], 'color_unique_set' => $r[8]));	
			//array_push($json, array('population' => $r[0], 'start' => floatval($rnaseq_expr[$rnaseq_column_index[$r[0]]]), 'value' => $rnaseq_max[$r[0]], 'color' => '#CCCCCC', 'short_name' => $r[2], 'long_name' => $r[3], 'description' => $r[4], 'author' => $r[5], 'number_of_sample' => $r[6], 'sorting_info' => $r[7], 'type' => 'max'));	
		}
		$output = array('meta'=>$rnaseq_meta, 'kegg'=>$kegg, 'go'=>$go, 'orthology'=>$orthology, 'data'=>$json, 'max'=>$rnaseq_max, 'pop_group_num'=>$ranseq_population_group_num);
		echo json_encode($output);
	}
	                                                                   
	

?>
