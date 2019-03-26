backgrounds.enable = 0;

function GameDetails_custom(servername, serverurl, mapname, maxplayers, steamid, gamemode) {
	showRules(gamemode);
	showStaff(gamemode);
}

function showRules(gamemode) {
	if (rules[gamemode] instanceof Array === false) { gamemode = 'global'; }
	if (rules[gamemode] instanceof Array) {
		if (rules[gamemode].length > 0) {
			var gm_rules = rules[gamemode];
			var real_cnt = 0;
			var rule_blocks = Math.ceil(gm_rules.length/5);

			for (var x = 0; x < rule_blocks; x++) {
				var children = [];
				for (var i = 0; i < 5; i++) {
					var rule = gm_rules[real_cnt] || null;
					real_cnt++;
					if (!rule) { continue; }

					children.push(elem('div',{ className: 'rule', innerText: rule}));
				}
				var rule_block = elem('div',{id: 'rule_block_'+x, className: 'rule-block'}, children);
				document.getElementById('rules').appendChild(rule_block);
				var rule_cnt = 0;
				var anim_dur = 1000;
				$('#rule_block_'+rule_cnt).children().each(function() {
					var elem = this;
					setTimeout(function(){
						$(elem).animate({opacity: '1'},{duration: 600});
					}, anim_dur);
					anim_dur = anim_dur+600;
				});
			}

			if (gm_rules.length > 5) {
				var rule_elems = $('.rule-block');
				rule_interval = setInterval(function(){
					$('#rule_block_'+rule_cnt).fadeOut(500, function() {
						anim_dur = 1000;
						rule_cnt++;
						if (rule_cnt >= rule_elems.length) {
							rule_cnt = 0;
						}
						$('#rule_block_'+rule_cnt).fadeIn(300);

						if ($('#rule_block_'+rule_cnt).children().css("opacity") == '0') {
							$('#rule_block_'+rule_cnt).children().each(function() {
								var elem = this;
								setTimeout(function(){
									$(elem).animate({opacity: '1'},{duration: 600});
								}, anim_dur);
								anim_dur = anim_dur+600;
							});
						}
					});
				}, 15000);
			}
		}
	}
}

function showStaff(gamemode) {
	if (Array.isArray(staff[gamemode]) === false) { gamemode = 'global'; }
	if (Array.isArray(staff[gamemode])) {
		if (staff[gamemode].length > 0) {
			var real_cnt = 0;
			var staff_blocks = Math.ceil(staff[gamemode].length/2);
			for (var i = 0; i < staff_blocks; i++) {
				var children = [];
				for (var x = 0; x < 2; x++) {
					var staff_member = staff[gamemode][real_cnt];
					real_cnt++;
					if (typeof staff_member === 'undefined') { continue; }
					var steamid = staff_member.steamid || null;
					var rank = staff_member.rank || 'Staff';

					children.push(
						elem('div',{className: 'member'}, [
							elem('img', {className: 'avatar small circle', src: site.path+'/api/player/'+steamid+'/avatarmedium?raw'}),
							elem('div', {},[
								elem('p', {className: 'name name_'+steamid, innerText: steamid}),
								elem('p', {className: 'rank rank_'+steamid, innerText: rank})
							])
						])
					);
				}
				document.getElementById('staff').appendChild(
					elem('div',{id: 'staff_block_'+i, className: 'staff-block'}, children)
				);
			}
			getStaff();
			var staff_cnt = 0;
			if (staff[gamemode].length > 2) {
				var staff_elems = $('.staff-block');
				staffinterval = setInterval(function(){
					$('#staff_block_'+staff_cnt).fadeOut(500, function() {
						anim_dur = 1000;
						staff_cnt++;
						if (staff_cnt >= staff_elems.length) {
							staff_cnt = 0;
						}
						$('#staff_block_'+staff_cnt).fadeIn(300);
					});
				}, 8000);
			}

		}
	}
}
