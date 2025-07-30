<?php
// Vérifie si l'utilisateur est connecté et a les droits d'administrateur.
// Si ce n'est pas le cas, une exception est levée pour refuser l'accès.
if (!isConnect('admin')) {
	throw new Exception(__('401 - Accès non autorisé', __FILE__));
}

// Récupère l'objet plugin correspondant à 'hp_printer'.
$plugin = plugin::byId('hp_printer');

// Envoie l'ID du plugin au JavaScript pour une utilisation côté client.
sendVarToJS('eqType', $plugin->getId());

// Récupère tous les équipements logiques (imprimantes) associés à ce type de plugin.
$eqLogics = eqLogic::byType($plugin->getId());
?>

<!-- Conteneur principal de la page, utilisant la classe `row-overflow` pour gérer le débordement. -->
<div class="row row-overflow">
	<!-- Section pour l'affichage des vignettes des équipements. -->
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		<!-- Légende pour la section de gestion. -->
		<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
		<!-- Conteneur pour les vignettes des actions de gestion. -->
		<div class="eqLogicThumbnailContainer">
			<!-- Vignette pour ajouter un nouvel équipement. -->
			<div class="cursor eqLogicAction logoPrimary" data-action="add">
				<i class="fas fa-plus-circle"></i>
				<br />
				<span>{{Ajouter}}</span>
			</div>
			<!-- Vignette pour accéder à la configuration du plugin. -->
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br />
				<span>{{Configuration}}</span>
			</div>
		</div>
		<!-- Légende pour la section des imprimantes HP. -->
		<legend><i class="fas fa-print"></i> {{Mes Imprimantes HP}}</legend>
		<?php
		// Vérifie si aucune imprimante n'est trouvée.
		if (count($eqLogics) == 0) {
			// Affiche un message si aucun équipement n'est configuré.
			echo '<br><div class="text-center" style="font-size:1.2em;font-weight:bold;">{{Aucune imprimante trouvée, cliquer sur "Ajouter" pour commencer}}</div>';
		} else {
			// Affiche la barre de recherche pour les équipements.
			echo '<div class="input-group" style="margin:5px;">';
			echo '<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic">';
			echo '<div class="input-group-btn">';
				// Bouton pour réinitialiser la recherche.
			echo '<a id="bt_resetSearch" class="btn" style="width:30px"><i class="fas fa-times"></i></a>';
				// Bouton pour changer l'affichage en tableau (caché par défaut).
			echo '<a class="btn roundedRight hidden" id="bt_pluginDisplayAsTable" data-coreSupport="1" data-state="0"><i class="fas fa-grip-lines"></i></a>';
			echo '</div>';
			echo '</div>';
			// Conteneur pour les vignettes des équipements.
			echo '<div class="eqLogicThumbnailContainer">';
			// Parcourt chaque équipement logique (imprimante).
			foreach ($eqLogics as $eqLogic) {
				// Détermine la classe CSS pour l'opacité si l'équipement est désactivé.
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				// Affiche la vignette de l'équipement.
				echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
				// Affiche l'icône du plugin.
				echo '<img src="' . $plugin->getPathImgIcon() . '">';
				echo '<br>';
				// Affiche le nom de l'équipement.
				echo '<span class="name">' . $eqLogic->getName() . '</span>';
				// Section cachée en mode vignette, affichée en mode tableau.
				echo '<span class="hiddenAsCard displayTableRight hidden">';
				// Affiche la fréquence de rafraîchissement si configurée.
				if ($eqLogic->getConfiguration('autorefresh', '') != '') {
					echo '<span class="label label-info" title="{{Fréquence de rafraîchissement des commandes}}">' . htmlspecialchars($eqLogic->getConfiguration('autorefresh')) . '</span>';
				}
				// Affiche une icône indiquant si l'équipement est visible ou non.
				echo ($eqLogic->getIsVisible() == 1) ? '<i class="fas fa-eye" title="{{Equipement visible}}"></i>' : '<i class="fas fa-eye-slash" title="{{Equipement non visible}}"></i>';
				echo '</span>';
				echo '</div>';
			}
			echo '</div>';
		}
		?>
	</div>

	<!-- Section pour l'affichage détaillé d'un équipement (initialement cachée). -->
	<div class="col-xs-12 eqLogic" style="display: none;">
		<!-- Groupe de boutons d'action pour l'équipement (sauvegarder, supprimer, etc.). -->
		<div class="input-group pull-right" style="display:inline-flex">
			<span class="input-group-btn">
				<!-- Bouton pour la configuration avancée. -->
				<a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i> {{Configuration avancée}}</a>
				<!-- Bouton pour dupliquer l'équipement. -->
				<a class="btn btn-default btn-sm eqLogicAction" data-action="copy"><i class="fas fa-copy"></i> {{Dupliquer}}</a>
				<!-- Bouton pour sauvegarder l'équipement. -->
				<a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>
				<!-- Bouton pour supprimer l'équipement. -->
				<a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
			</span>
		</div>
		<!-- Onglets de navigation pour l'équipement et les commandes. -->
		<ul class="nav nav-tabs" role="tablist">
			<!-- Onglet pour revenir à l'affichage des vignettes. -->
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<!-- Onglet pour les paramètres de l'équipement. -->
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
			<!-- Onglet pour les commandes de l'équipement. -->
			<li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list"></i> {{Commandes}}</a></li>
		</ul>
		<!-- Contenu des onglets. -->
		<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
			<!-- Panneau de l'onglet "Equipement". -->
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<!-- Formulaire pour les paramètres de l'équipement. -->
				<form class="form-horizontal">
					<fieldset>
						<div class="col-lg-6">
							<!-- Légende pour les paramètres généraux. -->
							<legend><i class="fas fa-wrench"></i> {{Paramètres généraux}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Nom de l'équipement}}</label>
								<div class="col-sm-6">
									<!-- Champ caché pour l'ID de l'équipement. -->
									<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display:none;">
									<!-- Champ pour le nom de l'équipement. -->
									<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Objet parent}}</label>
								<div class="col-sm-6">
									<!-- Sélecteur pour l'objet parent de l'équipement. -->
									<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
										<option value="">{{Aucun}}</option>
										<?php
										$options = '';
										// Construit la liste des objets Jeedom pour le sélecteur.
										foreach ((jeeObject::buildTree(null, false)) as $object) {
											$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
										}
										echo $options;
										?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Catégorie}}</label>
								<div class="col-sm-6">
									<?php
									// Affiche les cases à cocher pour les catégories de l'équipement.
									foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
										echo '<label class="checkbox-inline">';
										echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" >' . $value['name'];
										echo '</label>';
									}
									?>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Options}}</label>
								<div class="col-sm-6">
									<!-- Case à cocher pour activer/désactiver l'équipement. -->
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked>{{Activer}}</label>
									<!-- Case à cocher pour la visibilité de l'équipement sur le dashboard. -->
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked>{{Visible}}</label>
								</div>
							</div>

							<!-- Légende pour les paramètres spécifiques. -->
							<legend><i class="fas fa-cogs"></i> {{Paramètres spécifiques}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Adresse IP}}</label>
								<div class="col-sm-3">
									<!-- Champ pour l'adresse IP de l'imprimante. -->
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="ipAddress" placeholder="{{Ex: 192.168.1.10}}">
								</div>
								<div class="col-sm-5">
									<!-- Bouton pour tester la connexion à l'imprimante. -->
									<a class="btn btn-primary btn-sm" id="bt_testConnection"><i class="fas fa-check"></i> {{Tester la connexion}}</a>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Protocole}}</label>
								<div class="col-sm-6">
									<!-- Sélecteur pour le protocole (HTTP/HTTPS). -->
									<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="protocol">
										<option value="http">{{HTTP}}</option>
										<option value="https">{{HTTPS}}</option>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Auto-actualisation}}</label>
								<div class="col-sm-6">
									<div class="input-group">
										<!-- Champ pour la fréquence d'auto-actualisation (format cron). -->
										<input type="text" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="autorefresh" placeholder="{{Cliquer sur ? pour afficher l'assistant cron}}">
										<span class="input-group-btn">
											<!-- Bouton pour ouvrir l'assistant cron de Jeedom. -->
											<a class="btn btn-default cursor jeeHelper roundedRight" data-helper="cron" title="{{Assistant cron}}">
												<i class="fas fa-question-circle"></i>
											</a>
										</span>
									</div>
								</div>
							</div>
						</div>
					</fieldset>
				</form>
			</div>
			<!-- Panneau de l'onglet "Commandes". -->
			<div role="tabpanel" class="tab-pane" id="commandtab">
				<br />
				<!-- Table pour afficher et gérer les commandes de l'équipement. -->
				<table id="table_cmd" class="table table-bordered table-condensed">
					<thead>
						<tr>
							<th class="hidden-xs" style="min-width:50px;width:70px;">ID</th>
							<th style="min-width:200px;width:350px;">{{Nom}}</th>
							<th style="min-width:140px;width:200px;">{{Type}}</th>
							<th style="min-width:260px;">{{Options}}</th>
							<th>{{Etat}}</th>
							<th style="min-width:80px;width:140px;">{{Actions}}</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<?php 
	// Inclut les fichiers JavaScript nécessaires pour la gestion des commandes et le template de plugin.
	include_file('core', 'cmd', 'js');
	include_file('desktop', 'hp_printer', 'js', 'hp_printer');
	include_file('core', 'plugin.template', 'js');
	?>
</div>