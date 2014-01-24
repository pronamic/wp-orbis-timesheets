-- Add a legacy Orbis person ID column
ALTER TABLE orbis_hours_registration 
	ADD orbis_person_id BIGINT( 16 ) UNSIGNED NULL DEFAULT NULL AFTER user_id ,
	ADD INDEX ( orbis_person_id )
;

-- Set the legacy Orbis person ID column
UPDATE
	orbis_hours_registration
SET
	orbis_person_id = user_id
;

-- Add a WordPress user ID column
ALTER TABLE orbis_hours_registration 
	ADD wp_user_id BIGINT( 20 ) UNSIGNED NULL DEFAULT NULL AFTER user_id ,
	ADD INDEX ( wp_user_id )
;

-- Select WordPress user ID's with legacy Orbis person ID's
SELECT
	user.ID,
	user.display_name,
	meta.meta_value AS orbis_person_id
FROM
	wp_users AS user
		LEFT JOIN
	wp_usermeta AS meta
			ON  (
				meta.user_id = user.ID
				 	AND
				meta_key = 'orbis_legacy_person_id'
			)
;

-- Select registrations with Orbis person ID's and WordPress user ID's
SELECT
	registration.id,
	registration.orbis_person_id,
	user.ID,
	user.display_name
FROM
	orbis_hours_registration AS registration
		LEFT JOIN
	wp_usermeta AS meta
			ON (
				meta_key = 'orbis_legacy_person_id'
					AND
				meta_value = orbis_person_id
			)
		LEFT JOIN
	wp_users AS user
			ON meta.user_id = user.ID
;

-- Update registrations with WordPress user ID's
UPDATE
	orbis_hours_registration AS registration
		LEFT JOIN
	wp_usermeta AS meta
			ON (
				meta_key = 'orbis_legacy_person_id'
					AND
				meta_value = orbis_person_id
			)
		LEFT JOIN
	wp_users AS user
			ON meta.user_id = user.ID
SET
	wp_user_id = user.ID
;

-- Drop legacy user ID
ALTER TABLE orbis_hours_registration DROP user_id;

-- Rename WordPress user ID column
ALTER TABLE orbis_hours_registration CHANGE wp_user_id user_id BIGINT( 20 ) UNSIGNED NULL DEFAULT NULL;
