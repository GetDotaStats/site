SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


CREATE TABLE IF NOT EXISTS `kv_lod_bans` (
  `ability1` varchar(255) NOT NULL,
  `ability2` varchar(255) NOT NULL,
  `date_recorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ability1`,`ability2`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `kv_lod_bans` (`ability1`, `ability2`, `date_recorded`) VALUES
('alchemist_chemical_rage_lod', 'phoenix_supernova', '2014-08-01 07:24:38'),
('batrider_sticky_napalm', 'axe_battle_hunger', '2014-08-01 06:49:31'),
('batrider_sticky_napalm', 'batrider_flamebreak', '2014-08-01 06:49:31'),
('batrider_sticky_napalm', 'brewmaster_fire_permanent_immolation', '2014-08-01 06:56:17'),
('batrider_sticky_napalm', 'dark_seer_ion_shell', '2014-08-01 06:49:31'),
('batrider_sticky_napalm', 'doom_bringer_scorched_earth', '2014-08-01 06:56:17'),
('batrider_sticky_napalm', 'gyrocopter_rocket_barrage', '2014-08-01 06:49:31'),
('batrider_sticky_napalm', 'huskar_burning_spear', '2014-08-01 06:49:31'),
('batrider_sticky_napalm', 'juggernaut_blade_fury', '2014-08-01 06:49:31'),
('batrider_sticky_napalm', 'pudge_rot', '2014-08-01 06:49:31'),
('batrider_sticky_napalm', 'shadow_shaman_shackles', '2014-08-01 06:49:31'),
('batrider_sticky_napalm', 'skywrath_mage_mystic_flare', '2014-08-01 06:49:31'),
('batrider_sticky_napalm', 'slark_dark_pact', '2014-08-01 06:49:31'),
('batrider_sticky_napalm', 'venomancer_poison_sting', '2014-08-01 06:49:31'),
('batrider_sticky_napalm', 'viper_poison_attack', '2014-08-01 06:49:31'),
('batrider_sticky_napalm', 'warlock_golem_permanent_immolation', '2014-08-01 06:49:31'),
('bounty_hunter_jinada', 'kunkka_tidebringer', '2014-08-01 06:47:10'),
('bristleback_warpath', 'courier_go_to_secretshop', '2014-08-01 07:19:45'),
('bristleback_warpath', 'courier_return_stash_items', '2014-08-01 07:19:45'),
('bristleback_warpath', 'courier_return_to_base', '2014-08-01 07:19:45'),
('bristleback_warpath', 'courier_take_stash_items', '2014-08-01 07:19:45'),
('bristleback_warpath', 'courier_transfer_items', '2014-08-01 07:19:45'),
('bristleback_warpath', 'elder_titan_ancestral_spirit', '2014-08-01 07:19:45'),
('bristleback_warpath', 'furion_teleportation', '2014-08-01 07:19:45'),
('bristleback_warpath', 'leshrac_pulse_nova', '2014-08-01 07:19:45'),
('bristleback_warpath', 'medusa_mana_shield', '2014-08-01 07:19:45'),
('bristleback_warpath', 'medusa_split_shot', '2014-08-01 07:19:45'),
('bristleback_warpath', 'morphling_morph_agi', '2014-08-01 07:19:45'),
('bristleback_warpath', 'morphling_morph_str', '2014-08-01 07:19:45'),
('bristleback_warpath', 'pudge_rot', '2014-08-01 07:19:45'),
('bristleback_warpath', 'shredder_chakram', '2014-08-01 07:19:45'),
('bristleback_warpath', 'tinker_rearm', '2014-08-01 07:19:45'),
('bristleback_warpath', 'troll_warlord_berserkers_rage', '2014-08-01 07:19:45'),
('chaos_knight_reality_rift', 'weaver_geminate_attack', '2014-08-01 06:47:10'),
('earthshaker_aftershock', 'alchemist_unstable_concoction', '2014-08-01 07:03:56'),
('earthshaker_aftershock', 'bane_nightmare', '2014-08-01 07:03:56'),
('earthshaker_aftershock', 'batrider_sticky_napalm', '2014-08-01 07:03:56'),
('earthshaker_aftershock', 'bristleback_quill_spray', '2014-08-01 07:03:56'),
('earthshaker_aftershock', 'bristleback_viscous_nasal_goo', '2014-08-01 07:03:56'),
('earthshaker_aftershock', 'courier_burst', '2014-08-01 07:11:17'),
('earthshaker_aftershock', 'courier_go_to_secretshop', '2014-08-01 07:11:17'),
('earthshaker_aftershock', 'courier_return_stash_items', '2014-08-01 07:11:17'),
('earthshaker_aftershock', 'courier_return_to_base', '2014-08-01 07:11:17'),
('earthshaker_aftershock', 'courier_take_stash_items', '2014-08-01 07:11:17'),
('earthshaker_aftershock', 'courier_transfer_items', '2014-08-01 07:11:17'),
('earthshaker_aftershock', 'doom_bringer_devour', '2014-08-01 07:03:56'),
('earthshaker_aftershock', 'elder_titan_ancestral_spirit', '2014-08-01 07:03:56'),
('earthshaker_aftershock', 'forest_troll_high_priest_heal', '2014-08-01 07:11:17'),
('earthshaker_aftershock', 'furion_teleportation', '2014-08-01 07:11:17'),
('earthshaker_aftershock', 'leshrac_pulse_nova', '2014-08-01 07:03:56'),
('earthshaker_aftershock', 'lone_druid_true_form', '2014-08-01 07:03:56'),
('earthshaker_aftershock', 'medusa_mana_shield', '2014-08-01 07:03:56'),
('earthshaker_aftershock', 'medusa_split_shot', '2014-08-01 07:03:56'),
('earthshaker_aftershock', 'morphling_morph_agi', '2014-08-01 07:03:56'),
('earthshaker_aftershock', 'morphling_morph_str', '2014-08-01 07:03:56'),
('earthshaker_aftershock', 'obsidian_destroyer_arcane_orb', '2014-08-01 07:11:17'),
('earthshaker_aftershock', 'pudge_rot', '2014-08-01 07:11:17'),
('earthshaker_aftershock', 'pugna_life_drain', '2014-08-01 07:11:17'),
('earthshaker_aftershock', 'rubick_telekinesis', '2014-08-01 07:11:17'),
('earthshaker_aftershock', 'shadow_demon_shadow_poison', '2014-08-01 07:11:17'),
('earthshaker_aftershock', 'shredder_chakram', '2014-08-01 07:11:17'),
('earthshaker_aftershock', 'shredder_timber_chain', '2014-08-01 07:11:17'),
('earthshaker_aftershock', 'skywrath_mage_arcane_bolt', '2014-08-01 07:11:17'),
('earthshaker_aftershock', 'storm_spirit_ball_lightning', '2014-08-01 07:11:17'),
('earthshaker_aftershock', 'storm_spirit_static_remnant', '2014-08-01 07:11:17'),
('earthshaker_aftershock', 'tidehunter_anchor_smash', '2014-08-01 07:11:17'),
('earthshaker_aftershock', 'tinker_rearm', '2014-08-01 07:11:17'),
('earthshaker_aftershock', 'treant_natures_guise', '2014-08-01 07:11:17'),
('earthshaker_aftershock', 'troll_warlord_berserkers_rage', '2014-08-01 07:11:17'),
('earthshaker_aftershock', 'undying_decay', '2014-08-01 07:11:17'),
('earthshaker_aftershock', 'wisp_overcharge', '2014-08-01 07:11:17'),
('earthshaker_aftershock', 'witch_doctor_voodoo_restoration', '2014-08-01 07:11:17'),
('earthshaker_aftershock', 'zuus_arc_lightning', '2014-08-01 07:11:17'),
('earthshaker_enchant_totem', 'bounty_hunter_jinada', '2014-08-01 06:47:10'),
('earthshaker_enchant_totem', 'kunkka_tidebringer', '2014-08-01 06:47:10'),
('earthshaker_enchant_totem', 'tusk_walrus_punch', '2014-08-01 06:47:10'),
('earthshaker_enchant_totem', 'weaver_geminate_attack', '2014-08-01 06:47:10'),
('ember_spirit_flame_guard', 'batrider_sticky_napalm', '2014-08-01 07:21:39'),
('holdout_multishot', 'bristleback_warpath', '2014-08-01 07:21:39'),
('holdout_multishot', 'earthshaker_aftershock', '2014-08-01 07:21:39'),
('holdout_multishot', 'ember_spirit_sleight_of_fist', '2014-08-01 07:24:38'),
('holdout_multishot', 'holdout_omnislash', '2014-08-01 07:24:38'),
('holdout_multishot', 'obsidian_destroyer_essence_aura', '2014-08-01 07:21:39'),
('holdout_multishot', 'storm_spirit_overload', '2014-08-01 07:21:39'),
('holdout_multishot', 'zuus_static_field', '2014-08-01 07:21:39'),
('kunkka_tidebringer', 'tusk_walrus_punch', '2014-08-01 06:47:10'),
('lina_fiery_soul', 'abaddon_aphotic_shield', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'abaddon_death_coil', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'alchemist_unstable_concoction', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'ancient_apparition_ice_vortex', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'antimage_blink', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'axe_battle_hunger', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'bane_nightmare', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'batrider_sticky_napalm', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'bloodseeker_blood_rage', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'bounty_hunter_track', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'bristleback_quill_spray', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'bristleback_viscous_nasal_goo', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'chaos_knight_reality_rift', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'courier_burst', '2014-08-01 07:03:56'),
('lina_fiery_soul', 'courier_go_to_secretshop', '2014-08-01 07:03:56'),
('lina_fiery_soul', 'courier_return_stash_items', '2014-08-01 07:03:56'),
('lina_fiery_soul', 'courier_return_to_base', '2014-08-01 07:03:56'),
('lina_fiery_soul', 'courier_take_stash_items', '2014-08-01 07:03:56'),
('lina_fiery_soul', 'courier_transfer_items', '2014-08-01 07:03:56'),
('lina_fiery_soul', 'dazzle_shadow_wave', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'doom_bringer_devour', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'earthshaker_enchant_totem', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'elder_titan_ancestral_spirit', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'forest_troll_high_priest_heal', '2014-08-01 07:03:56'),
('lina_fiery_soul', 'furion_teleportation', '2014-08-01 07:03:56'),
('lina_fiery_soul', 'gyrocopter_rocket_barrage', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'holdout_multishot', '2014-08-01 07:21:39'),
('lina_fiery_soul', 'leshrac_lightning_storm', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'leshrac_pulse_nova', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'lich_ice_armor', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'lion_mana_drain', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'luna_lucent_beam', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'medusa_mana_shield', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'medusa_split_shot', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'meepo_poof', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'morphling_morph_agi', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'morphling_morph_str', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'necrolyte_death_pulse', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'obsidian_destroyer_arcane_orb', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'phantom_assassin_phantom_strike', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'puck_phase_shift', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'pudge_rot', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'pugna_nether_blast', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'queen_of_pain_blink', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'riki_blink_strike', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'rubick_telekinesis', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'shadow_demon_shadow_poison', '2014-08-01 06:56:17'),
('lina_fiery_soul', 'shredder_chakram', '2014-08-01 07:03:56'),
('lina_fiery_soul', 'shredder_timber_chain', '2014-08-01 07:03:56'),
('lina_fiery_soul', 'skywrath_mage_arcane_bolt', '2014-08-01 07:03:56'),
('lina_fiery_soul', 'slark_dark_pact', '2014-08-01 07:03:56'),
('lina_fiery_soul', 'storm_spirit_ball_lightning', '2014-08-01 07:03:56'),
('lina_fiery_soul', 'storm_spirit_static_remnant', '2014-08-01 07:03:56'),
('lina_fiery_soul', 'templar_assassin_meld', '2014-08-01 07:03:56'),
('lina_fiery_soul', 'tidehunter_anchor_smash', '2014-08-01 07:03:56'),
('lina_fiery_soul', 'tinker_rearm', '2014-08-01 07:03:56'),
('lina_fiery_soul', 'treant_natures_guise', '2014-08-01 07:03:56'),
('lina_fiery_soul', 'troll_warlord_berserkers_rage', '2014-08-01 07:03:56'),
('lina_fiery_soul', 'undying_decay', '2014-08-01 07:03:56'),
('lina_fiery_soul', 'venomancer_plague_ward', '2014-08-01 07:03:56'),
('lina_fiery_soul', 'weaver_shukuchi', '2014-08-01 07:03:56'),
('lina_fiery_soul', 'wisp_overcharge', '2014-08-01 07:03:56'),
('lina_fiery_soul', 'witch_doctor_voodoo_restoration', '2014-08-01 07:03:56'),
('lina_fiery_soul', 'zuus_arc_lightning', '2014-08-01 07:03:56'),
('lina_fiery_soul', 'zuus_lightning_bolt', '2014-08-01 07:03:56'),
('lone_druid_true_form', 'troll_warlord_berserkers_rage', '2014-08-01 07:19:45'),
('meepo_divided_we_stand', 'ancient_apparition_ice_blast', '2014-08-01 07:24:38'),
('meepo_divided_we_stand', 'furion_wrath_of_nature', '2014-08-01 07:24:38'),
('meepo_divided_we_stand', 'invoker_sun_strike_lod', '2014-08-01 07:24:38'),
('meepo_divided_we_stand', 'lone_druid_spirit_bear', '2014-08-01 07:24:38'),
('meepo_divided_we_stand', 'meepo_divided_we_stand', '2014-08-01 07:24:38'),
('meepo_divided_we_stand', 'zuus_thundergods_vengeance', '2014-08-01 07:24:38'),
('meepo_divided_we_stand', 'zuus_thundergods_wrath', '2014-08-01 07:24:38'),
('obsidian_destroyer_essence_aura', 'alchemist_unstable_concoction', '2014-08-01 07:11:17'),
('obsidian_destroyer_essence_aura', 'bane_nightmare', '2014-08-01 07:11:17'),
('obsidian_destroyer_essence_aura', 'courier_burst', '2014-08-01 07:15:52'),
('obsidian_destroyer_essence_aura', 'courier_go_to_secretshop', '2014-08-01 07:15:52'),
('obsidian_destroyer_essence_aura', 'courier_return_stash_items', '2014-08-01 07:15:52'),
('obsidian_destroyer_essence_aura', 'courier_return_to_base', '2014-08-01 07:15:52'),
('obsidian_destroyer_essence_aura', 'courier_take_stash_items', '2014-08-01 07:15:52'),
('obsidian_destroyer_essence_aura', 'courier_transfer_items', '2014-08-01 07:15:52'),
('obsidian_destroyer_essence_aura', 'doom_bringer_devour', '2014-08-01 07:11:17'),
('obsidian_destroyer_essence_aura', 'elder_titan_ancestral_spirit', '2014-08-01 07:11:17'),
('obsidian_destroyer_essence_aura', 'forest_troll_high_priest_heal', '2014-08-01 07:15:52'),
('obsidian_destroyer_essence_aura', 'furion_teleportation', '2014-08-01 07:15:52'),
('obsidian_destroyer_essence_aura', 'leshrac_pulse_nova', '2014-08-01 07:11:17'),
('obsidian_destroyer_essence_aura', 'medusa_mana_shield', '2014-08-01 07:11:17'),
('obsidian_destroyer_essence_aura', 'medusa_split_shot', '2014-08-01 07:11:17'),
('obsidian_destroyer_essence_aura', 'morphling_morph_agi', '2014-08-01 07:11:17'),
('obsidian_destroyer_essence_aura', 'morphling_morph_str', '2014-08-01 07:11:17'),
('obsidian_destroyer_essence_aura', 'pudge_rot', '2014-08-01 07:11:17'),
('obsidian_destroyer_essence_aura', 'rubick_telekinesis', '2014-08-01 07:11:17'),
('obsidian_destroyer_essence_aura', 'shredder_chakram', '2014-08-01 07:15:52'),
('obsidian_destroyer_essence_aura', 'storm_spirit_ball_lightning', '2014-08-01 07:11:17'),
('obsidian_destroyer_essence_aura', 'tinker_rearm', '2014-08-01 07:15:52'),
('obsidian_destroyer_essence_aura', 'troll_warlord_berserkers_rage', '2014-08-01 07:11:17'),
('obsidian_destroyer_essence_aura', 'wisp_overcharge', '2014-08-01 07:15:52'),
('obsidian_destroyer_essence_aura', 'witch_doctor_voodoo_restoration', '2014-08-01 07:15:52'),
('ogre_magi_multicast_lod', 'enigma_black_hole', '2014-08-01 07:24:38'),
('ogre_magi_multicast_lod', 'enraged_wildkin_tornado', '2014-08-01 07:24:38'),
('ogre_magi_multicast_lod', 'phoenix_sun_ray', '2014-08-01 07:24:38'),
('ogre_magi_multicast_lod', 'shadow_shaman_shackles', '2014-08-01 07:21:39'),
('phoenix_supernova', 'phoenix_supernova', '2014-08-01 07:24:38'),
('shredder_chakram', 'ogre_magi_multicast_lod', '2014-08-01 07:24:38'),
('storm_spirit_overload', 'alchemist_unstable_concoction', '2014-08-01 07:15:52'),
('storm_spirit_overload', 'bane_nightmare', '2014-08-01 07:15:52'),
('storm_spirit_overload', 'courier_burst', '2014-08-01 07:15:52'),
('storm_spirit_overload', 'courier_go_to_secretshop', '2014-08-01 07:15:52'),
('storm_spirit_overload', 'courier_return_stash_items', '2014-08-01 07:15:52'),
('storm_spirit_overload', 'courier_return_to_base', '2014-08-01 07:15:52'),
('storm_spirit_overload', 'courier_take_stash_items', '2014-08-01 07:15:52'),
('storm_spirit_overload', 'courier_transfer_items', '2014-08-01 07:15:52'),
('storm_spirit_overload', 'doom_bringer_devour', '2014-08-01 07:15:52'),
('storm_spirit_overload', 'elder_titan_ancestral_spirit', '2014-08-01 07:15:52'),
('storm_spirit_overload', 'forest_troll_high_priest_heal', '2014-08-01 07:15:52'),
('storm_spirit_overload', 'furion_teleportation', '2014-08-01 07:15:52'),
('storm_spirit_overload', 'medusa_mana_shield', '2014-08-01 07:15:52'),
('storm_spirit_overload', 'medusa_split_shot', '2014-08-01 07:15:52'),
('storm_spirit_overload', 'morphling_morph_agi', '2014-08-01 07:15:52'),
('storm_spirit_overload', 'morphling_morph_str', '2014-08-01 07:15:52'),
('storm_spirit_overload', 'obsidian_destroyer_arcane_orb', '2014-08-01 07:15:52'),
('storm_spirit_overload', 'pudge_rot', '2014-08-01 07:15:52'),
('storm_spirit_overload', 'rubick_telekinesis', '2014-08-01 07:15:52'),
('storm_spirit_overload', 'shredder_chakram', '2014-08-01 07:15:52'),
('storm_spirit_overload', 'tinker_rearm', '2014-08-01 07:15:52'),
('storm_spirit_overload', 'troll_warlord_berserkers_rage', '2014-08-01 07:15:52'),
('storm_spirit_overload', 'wisp_overcharge', '2014-08-01 07:15:52'),
('storm_spirit_overload', 'witch_doctor_voodoo_restoration', '2014-08-01 07:15:52'),
('tinker_rearm', 'alchemist_chemical_rage_lod', '2014-08-01 07:24:38'),
('tinker_rearm', 'ancient_apparition_ice_blast', '2014-08-01 07:21:39'),
('tinker_rearm', 'dark_seer_wall_of_replica', '2014-08-01 07:21:39'),
('tinker_rearm', 'faceless_void_chronosphere', '2014-08-01 07:21:39'),
('tinker_rearm', 'furion_wrath_of_nature', '2014-08-01 07:19:45'),
('tinker_rearm', 'invoker_sun_strike_lod', '2014-08-01 07:21:39'),
('tinker_rearm', 'magnataur_reverse_polarity', '2014-08-01 07:21:39'),
('tinker_rearm', 'necrolyte_reapers_scythe', '2014-08-01 07:21:39'),
('tinker_rearm', 'phoenix_supernova', '2014-08-01 07:21:39'),
('tinker_rearm', 'razor_eye_of_the_storm', '2014-08-01 07:21:39'),
('tinker_rearm', 'silencer_global_silence', '2014-08-01 07:21:39'),
('tinker_rearm', 'skeleton_king_reincarnation', '2014-08-01 07:19:45'),
('tinker_rearm', 'tidehunter_ravage', '2014-08-01 07:21:39'),
('tinker_rearm', 'treant_overgrowth', '2014-08-01 07:21:39'),
('tinker_rearm', 'warlock_rain_of_chaos', '2014-08-01 07:21:39'),
('tinker_rearm', 'zuus_thundergods_wrath', '2014-08-01 07:19:45'),
('tiny_grow', 'bounty_hunter_jinada', '2014-08-01 06:47:10'),
('tiny_grow', 'earthshaker_enchant_totem', '2014-08-01 06:47:10'),
('tiny_grow', 'weaver_geminate_attack', '2014-08-01 06:47:10'),
('witch_doctor_death_ward', 'ogre_magi_multicast_lod', '2014-08-01 07:24:38'),
('zuus_static_field', 'alchemist_unstable_concoction', '2014-08-01 07:15:52'),
('zuus_static_field', 'bane_nightmare', '2014-08-01 07:15:52'),
('zuus_static_field', 'courier_burst', '2014-08-01 07:19:45'),
('zuus_static_field', 'courier_go_to_secretshop', '2014-08-01 07:19:45'),
('zuus_static_field', 'courier_return_stash_items', '2014-08-01 07:19:45'),
('zuus_static_field', 'courier_return_to_base', '2014-08-01 07:19:45'),
('zuus_static_field', 'courier_take_stash_items', '2014-08-01 07:19:45'),
('zuus_static_field', 'courier_transfer_items', '2014-08-01 07:19:45'),
('zuus_static_field', 'doom_bringer_devour', '2014-08-01 07:15:52'),
('zuus_static_field', 'elder_titan_ancestral_spirit', '2014-08-01 07:15:52'),
('zuus_static_field', 'forest_troll_high_priest_heal', '2014-08-01 07:19:45'),
('zuus_static_field', 'furion_teleportation', '2014-08-01 07:19:45'),
('zuus_static_field', 'medusa_mana_shield', '2014-08-01 07:19:45'),
('zuus_static_field', 'medusa_split_shot', '2014-08-01 07:19:45'),
('zuus_static_field', 'morphling_morph_agi', '2014-08-01 07:19:45'),
('zuus_static_field', 'morphling_morph_str', '2014-08-01 07:19:45'),
('zuus_static_field', 'obsidian_destroyer_arcane_orb', '2014-08-01 07:19:45'),
('zuus_static_field', 'pudge_rot', '2014-08-01 07:19:45'),
('zuus_static_field', 'rubick_telekinesis', '2014-08-01 07:19:45'),
('zuus_static_field', 'shredder_chakram', '2014-08-01 07:19:45'),
('zuus_static_field', 'tinker_rearm', '2014-08-01 07:19:45'),
('zuus_static_field', 'troll_warlord_berserkers_rage', '2014-08-01 07:19:45'),
('zuus_static_field', 'wisp_overcharge', '2014-08-01 07:19:45'),
('zuus_static_field', 'witch_doctor_voodoo_restoration', '2014-08-01 07:19:45');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
