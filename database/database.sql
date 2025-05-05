DROP SCHEMA IF EXISTS lbaw24042 CASCADE;

CREATE SCHEMA IF NOT EXISTS lbaw24042;

SET search_path TO lbaw24042;

-- Drop Tables
DROP TABLE IF EXISTS "user";
DROP TABLE IF EXISTS badge;
DROP TABLE IF EXISTS user_badge;
DROP TABLE IF EXISTS tag;
DROP TABLE IF EXISTS question_post;
DROP TABLE IF EXISTS answer_post;
DROP TABLE IF EXISTS comment_post; 
DROP TABLE IF EXISTS question_like;
DROP TABLE IF EXISTS answer_like;
DROP TABLE IF EXISTS comment_like;
DROP TABLE IF EXISTS "notification";
DROP TABLE IF EXISTS user_follow_tag;

-- R01: User Table
CREATE TABLE "user" (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    tagname VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255),
    age INT CHECK (age >= 18),
    country VARCHAR(100),
    degree VARCHAR(255),
    is_admin BOOLEAN NOT NULL DEFAULT FALSE,
    is_moderator BOOLEAN NOT NULL DEFAULT FALSE,
    is_banned BOOLEAN NOT NULL DEFAULT FALSE,
    is_deleted BOOLEAN NOT NULL DEFAULT FALSE,
    is_dark_mode BOOLEAN NOT NULL DEFAULT FALSE,
    remember_token TEXT
    CHECK (NOT (is_admin AND is_moderator)),
    icon TEXT
);

-- R02: Badge Table
CREATE TABLE badge (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) UNIQUE NOT NULL,
    icon TEXT NOT NULL,
    description TEXT NOT NULL
);

-- R03: User_Badge Table
CREATE TABLE user_badge (
    user_id INT REFERENCES "user"(id) ON DELETE CASCADE,
    badge_id INT REFERENCES badge(id) ON DELETE CASCADE
);

-- R04: Tag Table
CREATE TABLE tag (
    id SERIAL PRIMARY KEY,
    color TEXT NOT NULL DEFAULT '#DCE4E8',
    color_text TEXT NOT NULL DEFAULT '#000',
    name VARCHAR(255) UNIQUE NOT NULL
);

-- R05: Question_Post Table
CREATE TABLE question_post (
    id SERIAL PRIMARY KEY,
    text TEXT NOT NULL,
    date TIMESTAMP NOT NULL CHECK (date <= NOW()),
    is_edited BOOLEAN NOT NULL DEFAULT FALSE,
    user_id INT REFERENCES "user"(id) ON DELETE CASCADE NOT NULL,
    title VARCHAR(255) UNIQUE NOT NULL,
    tag_id INT REFERENCES tag(id) ON DELETE SET NULL,
    nr_likes INT NOT NULL CHECK (nr_likes >= 0) DEFAULT 0
);

-- R06: Answer_Post Table
CREATE TABLE answer_post (
    id SERIAL PRIMARY KEY,
    text TEXT NOT NULL,
    date TIMESTAMP NOT NULL CHECK (date <= NOW()),
    is_edited BOOLEAN NOT NULL DEFAULT FALSE,
    is_correct BOOLEAN NOT NULL DEFAULT FALSE,
	nr_likes INT NOT NULL CHECK (nr_likes >= 0) DEFAULT 0,
    user_id INT REFERENCES "user"(id) ON DELETE CASCADE NOT NULL,   
	question_id INT REFERENCES question_post(id) ON DELETE CASCADE NOT NULL
);

-- R07: Comment_Post Table
CREATE TABLE comment_post (
    id SERIAL PRIMARY KEY,
    text TEXT NOT NULL,
    date TIMESTAMP NOT NULL CHECK (date <= NOW()),
    is_edited BOOLEAN NOT NULL DEFAULT FALSE,
	nr_likes INT NOT NULL CHECK (nr_likes >= 0) DEFAULT 0,
    user_id INT REFERENCES "user"(id) ON DELETE CASCADE NOT NULL,    
    answer_id INT REFERENCES answer_post(id) ON DELETE CASCADE NOT NULL 
);

-- R08: Question Like Table
CREATE TABLE question_like (
    user_id INT REFERENCES "user"(id) ON DELETE CASCADE,
    post_id INT REFERENCES question_post(id) ON DELETE CASCADE,
    date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, post_id)
);

-- R09: Answer Like Table
CREATE TABLE answer_like (
    user_id INT REFERENCES "user"(id) ON DELETE CASCADE,
    post_id INT REFERENCES answer_post(id) ON DELETE CASCADE,
    date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, post_id)
);

-- R10: Comment Like Table
CREATE TABLE comment_like (
    user_id INT REFERENCES "user"(id) ON DELETE CASCADE,
    post_id INT REFERENCES comment_post(id) ON DELETE CASCADE,
    date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, post_id)
);

-- R11: Notification Table
CREATE TABLE notification (
    id SERIAL PRIMARY KEY,
    user_receive_id INT REFERENCES "user"(id) ON DELETE CASCADE NOT NULL,
    user_trigger_id INT REFERENCES "user"(id) ON DELETE CASCADE,
    type VARCHAR(255) NOT NULL, -- 'question_answered', 'question_liked'...
    question_id INT REFERENCES question_post(id) ON DELETE CASCADE,
    answer_id INT REFERENCES answer_post(id) ON DELETE CASCADE,
    comment_id INT REFERENCES comment_post(id) ON DELETE CASCADE,
    badge_id INT REFERENCES badge(id) ON DELETE CASCADE,
    date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN NOT NULL DEFAULT FALSE
);

-- R16: User_Follow_Tag Table
CREATE TABLE user_follow_tag (
    user_id INT REFERENCES "user"(id) ON DELETE CASCADE NOT NULL,
    tag_id INT REFERENCES tag(id) ON DELETE CASCADE NOT NULL
);

-- R17: User_Follow_Question Table
CREATE TABLE user_follow_question (
    user_id INT REFERENCES "user"(id) ON DELETE CASCADE NOT NULL,
    question_id INT REFERENCES question_post(id) ON DELETE CASCADE NOT NULL,
    PRIMARY KEY (user_id, question_id)
);

-- R18: Post_Report_Reason Table
CREATE TABLE post_report_reason (
    id SERIAL PRIMARY KEY,
    reason TEXT NOT NULL UNIQUE
);

-- R19: Post_Report Table
CREATE TABLE post_report (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES "user"(id) ON DELETE CASCADE NOT NULL,
    post_type VARCHAR(50) NOT NULL CHECK (post_type IN ('question', 'answer', 'comment')),
    post_id INT NOT NULL,
    reason_id INT REFERENCES post_report_reason(id) ON DELETE CASCADE NOT NULL, 
    date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status TEXT NOT NULL DEFAULT 'open' CHECK (status IN ('open', 'resolved'))
);

-- Indexes
CREATE INDEX idx_users_not_deleted ON "user" (id) WHERE is_deleted = FALSE;
CREATE INDEX idx_question_tag ON question_post USING hash (tag_id);
CREATE INDEX idx_question_likes ON question_post (nr_likes);
CREATE INDEX idx_question_date ON question_post (date); 
CREATE INDEX idx_answers_correct_date ON answer_post (is_correct DESC, date DESC);
CREATE INDEX idx_notification_user_receive ON notification (user_receive_id, is_read, date DESC);
CREATE INDEX idx_notification_type ON notification (type);
CREATE INDEX idx_post_report_open ON post_report (status) WHERE status = 'open';
CREATE INDEX idx_question_id ON user_follow_question(question_id);
CREATE INDEX idx_user_id ON user_follow_question(user_id);
CREATE INDEX idx_question_user ON user_follow_question(question_id, user_id);

-- Full-Search Text Index
ALTER TABLE question_post
ADD COLUMN tsvectors TSVECTOR;

CREATE OR REPLACE FUNCTION question_title_search_update() 
RETURNS TRIGGER AS $$
BEGIN
    NEW.tsvectors := 
        setweight(to_tsvector('english', NEW.title), 'A') || 
        setweight(to_tsvector('english', NEW.text), 'B'); 
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER question_title_search_update_trigger
BEFORE INSERT OR UPDATE ON question_post
FOR EACH ROW
EXECUTE FUNCTION question_title_search_update();

CREATE INDEX idx_question_title_search ON question_post USING GIN (tsvectors);

-- Trigger: User cannot follow its own question
CREATE OR REPLACE FUNCTION prevent_self_follow()
RETURNS TRIGGER AS $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM question_post
        WHERE id = NEW.question_id AND user_id = NEW.user_id
    ) THEN
        RAISE EXCEPTION 'A user cannot follow their own question';
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER prevent_self_follow_trigger
BEFORE INSERT ON user_follow_question
FOR EACH ROW
EXECUTE FUNCTION prevent_self_follow();

-- Trigger 1: Update nr_likes on post when a new like is added
CREATE OR REPLACE FUNCTION update_post_like() RETURNS TRIGGER AS $$
BEGIN
    UPDATE question_post SET nr_likes = nr_likes + 1 WHERE id = NEW.post_id; 
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER question_like_trigger
AFTER INSERT ON question_like
FOR EACH ROW
EXECUTE FUNCTION update_post_like();

-- Trigger 3: Update nr_likes on post when a like is removed
CREATE OR REPLACE FUNCTION update_post_unlike() RETURNS TRIGGER AS $$
BEGIN
    UPDATE question_post SET nr_likes = nr_likes - 1 WHERE id = OLD.post_id; 
    RETURN OLD;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER question_unlike_trigger
AFTER DELETE ON question_like
FOR EACH ROW
EXECUTE FUNCTION update_post_unlike();

-- Trigger 4: Update nr_likes on answer post when a new like is added
CREATE OR REPLACE FUNCTION update_answer_like() RETURNS TRIGGER AS $$
BEGIN
    UPDATE answer_post SET nr_likes = nr_likes + 1 WHERE id = NEW.post_id; 
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER answer_like_trigger
AFTER INSERT ON answer_like
FOR EACH ROW
EXECUTE FUNCTION update_answer_like();

-- Trigger 6: Update nr_likes on answer post when a like is removed
CREATE OR REPLACE FUNCTION update_answer_unlike() RETURNS TRIGGER AS $$
BEGIN
    UPDATE answer_post SET nr_likes = nr_likes - 1 WHERE id = OLD.post_id; 
    RETURN OLD;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER answer_unlike_trigger
AFTER DELETE ON answer_like
FOR EACH ROW
EXECUTE FUNCTION update_answer_unlike();

-- Trigger 7: Update nr_likes on comment post when a new like is added
CREATE OR REPLACE FUNCTION update_comment_like() RETURNS TRIGGER AS $$
BEGIN
    UPDATE comment_post SET nr_likes = nr_likes + 1 WHERE id = NEW.post_id; 
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER comment_like_trigger
AFTER INSERT ON comment_like
FOR EACH ROW
EXECUTE FUNCTION update_comment_like();

-- Trigger 9: Update nr_likes on comment post when a like is removed
CREATE OR REPLACE FUNCTION update_comment_unlike() RETURNS TRIGGER AS $$
BEGIN
    UPDATE comment_post SET nr_likes = nr_likes - 1 WHERE id = OLD.post_id; 
    RETURN OLD;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER comment_unlike_trigger
AFTER DELETE ON comment_like
FOR EACH ROW
EXECUTE FUNCTION update_comment_unlike();

-- Trigger 10: Resolve report when post is deleted
CREATE OR REPLACE FUNCTION resolve_report_on_delete() RETURNS TRIGGER AS $$
DECLARE
    resolved_post_type TEXT;
BEGIN
    IF TG_TABLE_NAME = 'question_post' THEN
        resolved_post_type := 'question';
    ELSIF TG_TABLE_NAME = 'answer_post' THEN
        resolved_post_type := 'answer';
    ELSIF TG_TABLE_NAME = 'comment_post' THEN
        resolved_post_type := 'comment';
    ELSE
        RAISE EXCEPTION 'Unknown table name: %', TG_TABLE_NAME;
    END IF;

    UPDATE post_report
    SET status = 'resolved'
    WHERE post_id = OLD.id AND post_type = resolved_post_type;

    RETURN OLD;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER resolve_report_on_question_delete
AFTER DELETE ON question_post
FOR EACH ROW
EXECUTE FUNCTION resolve_report_on_delete();

CREATE TRIGGER resolve_report_on_answer_delete
AFTER DELETE ON answer_post
FOR EACH ROW
EXECUTE FUNCTION resolve_report_on_delete();

CREATE TRIGGER resolve_report_on_comment_delete
AFTER DELETE ON comment_post
FOR EACH ROW
EXECUTE FUNCTION resolve_report_on_delete();

-- Trigger 10: Create a notification when a question is answered
CREATE OR REPLACE FUNCTION notify_question_answered() RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO notification (user_receive_id, user_trigger_id, type, question_id, answer_id, date, is_read)
    VALUES (
        (SELECT user_id FROM question_post WHERE id = NEW.question_id), 
        NEW.user_id, 
        'question_answered',
        NEW.question_id, 
        NEW.id, 
        NOW(), 
        FALSE 
    );
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER question_answered_notification_trigger
AFTER INSERT ON answer_post
FOR EACH ROW
EXECUTE FUNCTION notify_question_answered();

-- Trigger 11: Create a notification when a question is liked
CREATE OR REPLACE FUNCTION notify_question_liked() RETURNS TRIGGER AS $$
BEGIN
    IF (SELECT user_id FROM question_post WHERE id = NEW.post_id) != NEW.user_id THEN
        INSERT INTO notification (user_receive_id, user_trigger_id, type, question_id, date, is_read)
        VALUES (
            (SELECT user_id FROM question_post WHERE id = NEW.post_id), 
            NEW.user_id, 
            'question_liked', 
            NEW.post_id, 
            NOW(), 
            FALSE 
        );
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER question_liked_notification_trigger
AFTER INSERT ON question_like
FOR EACH ROW
EXECUTE FUNCTION notify_question_liked();

-- Trigger 12: Create a notification when an answer is liked
CREATE OR REPLACE FUNCTION notify_answer_liked() RETURNS TRIGGER AS $$
DECLARE
    answer_author INT;
BEGIN
    SELECT user_id INTO answer_author
    FROM answer_post
    WHERE id = NEW.post_id;

    IF answer_author IS NOT NULL AND answer_author <> NEW.user_id THEN
        INSERT INTO notification (user_receive_id, user_trigger_id, type, answer_id, date, is_read)
        VALUES (
            answer_author,   
            NEW.user_id,     
            'answer_liked',  
            NEW.post_id,     
            NOW(),           
            FALSE            
        );
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER answer_liked_notification_trigger
AFTER INSERT ON answer_like
FOR EACH ROW
EXECUTE FUNCTION notify_answer_liked();

-- Trigger 13: Create a notification when a comment is liked
CREATE OR REPLACE FUNCTION notify_comment_liked() RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO notification (user_receive_id, user_trigger_id, type, comment_id, date, is_read)
    VALUES (
        (SELECT user_id FROM comment_post WHERE id = NEW.post_id), 
        NEW.user_id, 
        'comment_liked', 
        NEW.post_id, 
        NOW(), 
        FALSE
    );
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER comment_liked_notification_trigger
AFTER INSERT ON comment_like
FOR EACH ROW
EXECUTE FUNCTION notify_comment_liked();

-- Trigger 14: Create a notification when a comment is made on an answer
CREATE OR REPLACE FUNCTION notify_answer_commented() RETURNS TRIGGER AS $$
DECLARE
    answer_author INT;
BEGIN
    SELECT user_id INTO answer_author
    FROM answer_post
    WHERE id = NEW.answer_id; 

    IF answer_author IS NOT NULL AND answer_author <> NEW.user_id THEN
        INSERT INTO notification (user_receive_id, user_trigger_id, type, answer_id, comment_id, date, is_read)
        VALUES (
            answer_author, 
            NEW.user_id, 
            'answer_commented', 
            NEW.answer_id, 
            NEW.id, 
            NOW(),
            FALSE
        );
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER answer_commented_notification_trigger
AFTER INSERT ON comment_post
FOR EACH ROW
EXECUTE FUNCTION notify_answer_commented();

-- Trigger 15: Create a notification when a comment is made on a question
CREATE OR REPLACE FUNCTION notify_question_commented() RETURNS TRIGGER AS $$
DECLARE
    question_author INT;
BEGIN
    SELECT question_post.user_id INTO question_author
    FROM question_post 
    JOIN answer_post ON question_post.id = answer_post.question_id
    WHERE answer_post.id = NEW.answer_id;

    -- If a question author is found, insert a notification
    IF question_author IS NOT NULL AND question_author != NEW.user_id THEN
        INSERT INTO notification (user_receive_id, user_trigger_id, type, question_id, comment_id, date, is_read)
        VALUES (
            question_author,            
            NEW.user_id,                
            'question_commented',       
            (SELECT question_id         
             FROM answer_post 
             WHERE id = NEW.answer_id),
            NEW.id,                     
            NOW(),                      
            FALSE                       
        );
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER question_commented_notification_trigger
AFTER INSERT ON comment_post
FOR EACH ROW
EXECUTE FUNCTION notify_question_commented();


-- Trigger 15: Create a notification when an answer is marked as correct
CREATE OR REPLACE FUNCTION notify_answer_correct() RETURNS TRIGGER AS $$
DECLARE
    question_author INT;
BEGIN
    -- Question Author
    SELECT user_id INTO question_author
    FROM question_post
    WHERE id = NEW.question_id;

    IF question_author IS NOT NULL AND NEW.is_correct = TRUE THEN
        INSERT INTO notification (user_receive_id, user_trigger_id, type, question_id, answer_id)
        VALUES (
            NEW.user_id,           
            question_author,       
            'answer_correct', 
            NEW.question_id,       
            NEW.id
        );
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER answer_marked_correct_notification_trigger
AFTER UPDATE OF is_correct ON answer_post
FOR EACH ROW
WHEN (OLD.is_correct IS DISTINCT FROM NEW.is_correct AND NEW.is_correct = TRUE)
EXECUTE FUNCTION notify_answer_correct();

CREATE TRIGGER answer_insert_notification_trigger
AFTER INSERT ON answer_post
FOR EACH ROW
WHEN (NEW.is_correct = TRUE)  
EXECUTE FUNCTION notify_answer_correct();

-- Trigger 15: Trigger for Anonymizing Users
CREATE OR REPLACE FUNCTION anonymize_user() 
RETURNS TRIGGER AS $$
BEGIN
    -- Update user data to anonymized values instead of deleting
    UPDATE "user"
    SET
        name = 'Anonymous',                 
        tagname = 'anonymous_' || OLD.id,  
        email = NULL,
        password = NULL,
        age = NULL,
        country = NULL,
        degree = NULL,
        icon = NULL,
        is_admin = FALSE,
        is_moderator = FALSE,
        is_banned = TRUE,
        is_deleted = TRUE
    WHERE id = OLD.id;  
    
    RETURN NULL;  -- Prevent actual DELETE operation
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_anonymize_user
BEFORE DELETE ON "user"
FOR EACH ROW
EXECUTE FUNCTION anonymize_user();

-- Trigger 16: Trigger for the "Expert" Badge (100 Likes Across All Answers)
CREATE OR REPLACE FUNCTION assign_badge(p_user_id INT, p_badge_name TEXT) RETURNS VOID AS $$
DECLARE
    v_badge_id INT;  
BEGIN
    -- Find the badge ID based on the badge name
    SELECT id INTO v_badge_id
    FROM badge 
    WHERE title = p_badge_name;  

    -- Insert the badge only if it doesn't already exist for the user
    IF NOT EXISTS (
        SELECT 1 FROM user_badge
        WHERE user_badge.user_id = p_user_id AND user_badge.badge_id = v_badge_id
    ) THEN
        INSERT INTO user_badge (user_id, badge_id) VALUES (p_user_id, v_badge_id);
    END IF;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION check_expert_badge() RETURNS TRIGGER AS $$
DECLARE
    total_likes INT;
BEGIN
    -- Count total likes across all answers by the user
    SELECT COUNT(*) INTO total_likes
    FROM answer_like l
    JOIN answer_post a ON l.post_id = a.id 
    WHERE a.user_id = NEW.user_id;
    
    -- Assign the Expert badge if total likes reach 100
    IF total_likes >= 100 THEN
        PERFORM assign_badge(NEW.user_id, 'Expert');
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER expert_badge_trigger
AFTER INSERT ON answer_like
FOR EACH ROW
EXECUTE FUNCTION check_expert_badge();

-- Trigger 17: Trigger for the "Contributor" Badge (First Question)
CREATE OR REPLACE FUNCTION check_contributor_badge() RETURNS TRIGGER AS $$
BEGIN
    -- Assign the Contributor badge if the user creates their first question
    PERFORM assign_badge(NEW.user_id, 'Contributor');
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER contributor_badge_trigger
AFTER INSERT ON question_post
FOR EACH ROW 
EXECUTE FUNCTION check_contributor_badge();

-- Trigger 18: Trigger for the "Top Answerer" Badge (50 Likes on a Single Answer)
CREATE OR REPLACE FUNCTION check_top_answerer_badge() RETURNS TRIGGER AS $$
DECLARE
    answer_likes INT;
BEGIN
    -- Count likes for the specific answer
    SELECT COUNT(*) INTO answer_likes
    FROM answer_like
    WHERE post_id = NEW.post_id;  

    -- Assign the Top Answerer badge if likes reach 50 on this answer
    IF answer_likes >= 50 THEN
        PERFORM assign_badge(NEW.user_id, 'Top Answerer');
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER top_answerer_badge_trigger
AFTER INSERT ON answer_like
FOR EACH ROW 
EXECUTE FUNCTION check_top_answerer_badge();

-- Trigger 19: Trigger for the "Rising Star" Badge (10 Answers)
CREATE OR REPLACE FUNCTION check_rising_star_badge() RETURNS TRIGGER AS $$
DECLARE
    total_answers INT;
BEGIN
    -- Count total answers by the user
    SELECT COUNT(*) INTO total_answers
    FROM answer_post
    WHERE user_id = NEW.user_id;

    -- Assign the Rising Star badge if answers reach 10
    IF total_answers = 10 THEN
        PERFORM assign_badge(NEW.user_id, 'Rising Star');
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER rising_star_badge_trigger
AFTER INSERT ON answer_post
FOR EACH ROW EXECUTE FUNCTION check_rising_star_badge();

-- Trigger 20: Trigger for the "Community Leader" Badge (50 Posts)
CREATE OR REPLACE FUNCTION check_community_leader_badge() RETURNS TRIGGER AS $$
DECLARE
    total_posts INT;
BEGIN
    -- Count total posts by the user (questions + answers)
    SELECT 
        (SELECT COUNT(*) FROM question_post WHERE user_id = NEW.user_id) +
        (SELECT COUNT(*) FROM answer_post WHERE user_id = NEW.user_id)
    INTO total_posts;

    -- Assign the Community Leader badge if total posts reach 50
    IF total_posts >= 50 THEN
        PERFORM assign_badge(NEW.user_id, 'Community Leader');
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER community_leader_badge_trigger_question
AFTER INSERT ON question_post
FOR EACH ROW
EXECUTE FUNCTION check_community_leader_badge();

CREATE TRIGGER community_leader_badge_trigger_answer
AFTER INSERT ON answer_post
FOR EACH ROW 
EXECUTE FUNCTION check_community_leader_badge();

-- Trigger 22: make only one correct answer for each question
CREATE OR REPLACE FUNCTION single_correct_answer()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE answer_post
    SET is_correct = FALSE
    WHERE question_id = NEW.question_id AND id != NEW.id;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER single_correct_answer_trigger
BEFORE UPDATE OF is_correct
ON answer_post
FOR EACH ROW
WHEN (NEW.is_correct = TRUE)
EXECUTE FUNCTION single_correct_answer();

-- Function: Notify on tag update by admin or moderator
CREATE OR REPLACE FUNCTION notify_on_tag_update()
RETURNS TRIGGER AS $$
DECLARE
    updater_id INT;
    is_admin_or_moderator BOOLEAN;
BEGIN
    -- Get the ID of the user performing the update
    SELECT current_setting('app.current_user_id')::INT INTO updater_id;
   
    -- Skip if updater is the same as the author
    IF updater_id = OLD.user_id THEN
        RETURN NEW;
    END IF;

    -- Check if the user is an admin or moderator
    SELECT is_admin OR is_moderator INTO is_admin_or_moderator
    FROM "user"
    WHERE id = updater_id;

    -- Insert a notification
    INSERT INTO notification (
        user_receive_id, 
        user_trigger_id, 
        type,            
        question_id,     
        date,            
        is_read          
    )
    VALUES (
        OLD.user_id,     
        CASE WHEN is_admin_or_moderator THEN NULL ELSE updater_id END,      
        'edit_tag',      
        OLD.id,          
        NOW(),           
        FALSE            
    );

    RETURN NEW; 
END;
$$ LANGUAGE plpgsql;

-- Trigger: Notify on tag update
CREATE TRIGGER notify_on_tag_update_trigger
AFTER UPDATE OF tag_id ON question_post
FOR EACH ROW
EXECUTE FUNCTION notify_on_tag_update();

-- Trigger 24: Question deleted by admin
CREATE OR REPLACE FUNCTION notify_on_question_delete()
RETURNS TRIGGER AS $$
DECLARE
    updater_id INT;
    is_admin_or_moderator BOOLEAN;
BEGIN
    -- Get the ID of the user performing the update
    SELECT current_setting('app.current_user_id')::INT INTO updater_id;

    -- Skip if updater is the same as the author
    IF updater_id = OLD.user_id THEN
        RETURN NEW;
    END IF;

    -- Check if the user is an admin or moderator
    SELECT is_admin OR is_moderator INTO is_admin_or_moderator
    FROM "user"
    WHERE id = updater_id;

    -- Insert a notification
    INSERT INTO notification (
        user_receive_id, 
        user_trigger_id, 
        type,            
        date,            
        is_read          
    )
    VALUES (
        OLD.user_id,     
        CASE WHEN is_admin_or_moderator THEN NULL ELSE updater_id END,      
        'question_deleted',      
        NOW(),           
        FALSE            
    );

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_question_delete
AFTER DELETE ON question_post
FOR EACH ROW
EXECUTE FUNCTION notify_on_question_delete();

-- Trigger 24: Question deleted by admin
CREATE OR REPLACE FUNCTION notify_on_answer_delete()
RETURNS TRIGGER AS $$
DECLARE
    updater_id INT;
    is_admin_or_moderator BOOLEAN;
BEGIN
    -- Get the ID of the user performing the update
    SELECT current_setting('app.current_user_id')::INT INTO updater_id;

    -- Skip if updater is the same as the author
    IF updater_id = OLD.user_id THEN
        RETURN NEW;
    END IF;

    -- Check if the user is an admin or moderator
    SELECT is_admin OR is_moderator INTO is_admin_or_moderator
    FROM "user"
    WHERE id = updater_id;

    -- Insert a notification
    INSERT INTO notification (
        user_receive_id, 
        user_trigger_id, 
        type,            
        date,            
        is_read          
    )
    VALUES (
        OLD.user_id,     
        CASE WHEN is_admin_or_moderator THEN NULL ELSE updater_id END,      
        'answer_deleted',      
        NOW(),           
        FALSE            
    );

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_answer_delete
AFTER DELETE ON answer_post
FOR EACH ROW
EXECUTE FUNCTION notify_on_answer_delete();

-- Trigger 24: Question deleted by admin
CREATE OR REPLACE FUNCTION notify_on_comment_delete()
RETURNS TRIGGER AS $$
DECLARE
    updater_id INT;
    is_admin_or_moderator BOOLEAN;
BEGIN
    -- Get the ID of the user performing the update
    SELECT current_setting('app.current_user_id')::INT INTO updater_id;

    -- Skip if updater is the same as the author
    IF updater_id = OLD.user_id THEN
        RETURN NEW;
    END IF;

    -- Check if the user is an admin or moderator
    SELECT is_admin OR is_moderator INTO is_admin_or_moderator
    FROM "user"
    WHERE id = updater_id;

    -- Insert a notification
    INSERT INTO notification (
        user_receive_id, 
        user_trigger_id, 
        type,            
        date,            
        is_read          
    )
    VALUES (
        OLD.user_id,     
        CASE WHEN is_admin_or_moderator THEN NULL ELSE updater_id END,      
        'comment_deleted',      
        NOW(),           
        FALSE            
    );

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_comment_delete
AFTER DELETE ON comment_post
FOR EACH ROW
EXECUTE FUNCTION notify_on_comment_delete();

-- Trigger 25: Receive badge trigger
CREATE OR REPLACE FUNCTION notify_on_badge_received()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO notification (
        user_receive_id,    
        user_trigger_id,  
        type,          
        badge_id,         
        is_read        
    )
    VALUES (
        NEW.user_id,        
        NULL,          
        'badge_received',   
        NEW.badge_id,           
        FALSE              
    );

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_badge_received
AFTER INSERT ON user_badge
FOR EACH ROW
EXECUTE FUNCTION notify_on_badge_received();

-- Trigger: default like on question by user that creates it 
CREATE OR REPLACE FUNCTION add_question_like()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO question_like (user_id, post_id)
    VALUES (NEW.user_id, NEW.id);
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_auto_like_question
AFTER INSERT ON question_post
FOR EACH ROW
EXECUTE FUNCTION add_question_like();

-- Trigger: default like on answer by user that creates itCREATE OR REPLACE FUNCTION add_answer_like()
CREATE OR REPLACE FUNCTION add_answer_like()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO answer_like (user_id, post_id)
    VALUES (NEW.user_id, NEW.id);
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_auto_like_answer
AFTER INSERT ON answer_post
FOR EACH ROW
EXECUTE FUNCTION add_answer_like();

-- Trigger: default like on comment by user that creates it
CREATE OR REPLACE FUNCTION add_comment_like()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO comment_like (user_id, post_id)
    VALUES (NEW.user_id, NEW.id);
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_auto_like_comment
AFTER INSERT ON comment_post
FOR EACH ROW
EXECUTE FUNCTION add_comment_like();

-- Transaction 1: Insert answer if question exists
CREATE OR REPLACE FUNCTION AddAnswerIfQuestionExists(
    question_id INT,
    answer_content TEXT,
    user_id INT
)
RETURNS VOID AS $$
BEGIN
    BEGIN
        IF EXISTS (SELECT 1 FROM question_post WHERE id = question_id) THEN 
            INSERT INTO answer_post(text, date, is_edited, user_id, nr_likes) 
            VALUES (answer_content, NOW(), FALSE, user_id, 0);
        ELSE
            RAISE EXCEPTION 'Question does not exist';
        END IF;

    EXCEPTION
        WHEN OTHERS THEN
            RAISE EXCEPTION 'An error occurred while inserting the answer';
    END;
END;
$$ LANGUAGE plpgsql;

-- Transaction 2: Insert comment if both question and answer exist
CREATE OR REPLACE FUNCTION AddCommentIfQuestionAndAnswerExist(
    question_id INT,
    answer_id INT,
    comment_content TEXT,
    user_id INT
)
RETURNS VOID AS $$
BEGIN
    BEGIN
        IF EXISTS (SELECT 1 FROM question_post WHERE id = question_id) 
           AND EXISTS (SELECT 1 FROM answer_post WHERE id = answer_id) THEN 
            INSERT INTO comment_post(text, date, is_edited, user_id, nr_likes) 
            VALUES (comment_content, NOW(), FALSE, user_id, 0);
        ELSE
            RAISE EXCEPTION 'Question or answer does not exist';
        END IF;

    EXCEPTION
        WHEN OTHERS THEN
            RAISE EXCEPTION 'An error occurred while inserting the comment';
    END;
END;
$$ LANGUAGE plpgsql;

SET search_path TO lbaw24042;


-- Populate User Table
INSERT INTO "user" (id, name, tagname, email, password, age, country, degree, is_admin, is_moderator, is_banned,icon)
VALUES 
(1, 'João Silva', 'joao.s', 'joao.silva@gmail.com', '$2b$12$9BOLE0lEz1FGF9V9UgY78.XHFAZH9Yy0hLQa7Dn5N/kwCtKfEq/GC', 22, 'Portugal', 'Master', false, false, false, 'images/profile/user1.png'),
(2, 'Mariana Costa', 'mariana.c', 'mariana.costa@gmail.com', '$2b$12$DmuXa1KtA/hlCmkKBcN16eA/.Sek0weAqObHKYUGkLcMf/iFHJJbu', 23, 'Portugal', 'Bachelor', false, false, false,'images/profile/user2.png'),
(3, 'Francisco Santos', 'francisco.s', 'francisco.santos@gmail.com', '$2b$12$2gA3mv5N.eH8nBiP/X66D.zHBcBCHZTR2enYHe/Lf/whIqX9ojJzC', 25, 'Portugal', 'PhD', false, false, false,'images/profile/user3.png'),
(4, 'Inês Mendes', 'ines.m', 'ines.mendes@gmail.com', '$2b$12$jVwMfg8J3EFcCe/gVTuXNOTdXa9S72rtee4nu78sILupzscm0trIC', 21, 'Portugal', 'Bachelor', true, false, false, 'images/profile/user4.png'),
(5, 'Rui Ferreira', 'rui.f', 'rui.ferreira@gmail.com', '$2b$12$g.HfQTWwIhuK7ofCXCXps.UnNqAURIwMLJQH08YX7jYCF5rjP8a5.', 24, 'Portugal', 'Master', false, true, false,'images/profile/user5.png'),
(6, 'Carla Oliveira', 'carla.o', 'carla.oliveira@gmail.com', '$2b$12$qyMnwCV3Ztemit1ANp4HROE0dXkZTVWOiS6h.yyeqpQmf0Pwv5Z0e', 20, 'Portugal', 'Bachelor', false, false, true,'images/profile/user6.png'),
(7, 'André Silva', 'andre.s', 'andre.silva@gmail.com', '$2b$12$u4.jlalQ92P0ZXz6g8mjD.3rA3xvsdzP8WhrhiLz5j0yoHGE6DBKu', 28, 'Portugal', 'Master', false, false, false,'images/profile/user7.png'),
(8, 'Ana Marques', 'ana.m', 'ana.marques@gmail.com', '$2b$12$iLsPVRsr.gLOcRl7a0Rgx.dnfF4Lf4si1vtdLjYGGs3oVpdD2wuN6', 21, 'Portugal', 'Bachelor', false, false, false,'images/profile/user8.png'),
(9, 'Tomás Ferreira', 'tomas.f', 'tomas.ferreira@gmail.com', '$2b$12$YKMIN8pJ4dsWGAll3IU0xOZdF8GzCDFHTzJP.vpF/VfGL6jk7uMKW', 27, 'Portugal', 'PhD', true, false, false,'images/profile/user9.png'),
(10, 'Sofia Nunes', 'sofia.n', 'sofia.nunes@gmail.com', '$2b$12$hUgLIzry.w6mxHUTQ1LkeOhehyElDrTQk70lw5bn8bO9Iv4HRuruq', 29, 'Portugal', 'Master', false, false, false,'images/profile/user10.png'),
(11, 'Luís Almeida', 'luis.a', 'luis.almeida@gmail.com', '$2b$12$PbfofIqV93/6yQRNvgfICOmawNkGTfTa88kGcGPp.GOpBC1iKz5R6', 23, 'Portugal', 'Bachelor', false, true, false,'images/profile/user11.png'),
(12, 'Sara Sousa', 'sara.s', 'sara.sousa@gmail.com', '$2b$12$47/c/3ailfa8/gVuH6HF/u7icnJ/C11SWA1/7A9swPJh02qFFZtQ6', 26, 'Portugal', 'Master', false, false, false,'images/profile/user12.png'),
(13, 'Miguel Rocha', 'miguel.r', 'miguel.rocha@gmail.com', '$2b$12$LvU.2TEDkodThUONpNIt7es3D0VrciisPauQFq6FEheu9Q1V.7sFy', 24, 'Portugal', 'Bachelor', false, false, false,'images/profile/user13.png'),
(14, 'Raquel Antunes', 'raquel.a', 'raquel.antunes@gmail.com', '$2b$12$fVUfem..hzz8Jzz5fcfwCOQoJ8dCigf2Nge2iOjo7rGjX1/hTr4Re', 25, 'Portugal', 'PhD', true, false, false,'images/profile/user14.png'),
(15, 'Vasco Ribeiro', 'vasco.r', 'vasco.ribeiro@gmail.com', '$2b$12$84B5sXyLiW.Gt4CUpdJgb.ba87M9CGR3Cn2T1M3AgLt1za9gE28vW', 27, 'Portugal', 'Master', false, false, false,'images/profile/user15.png'),
(16, 'Ricardo Figueiredo', 'ricardo.f', 'ricardo@gmail.com', '$2b$12$sUhUdI2BtEQD6xy3eE1n.ecJT.cT9P4nC9rU9ZkKsItAQV6qax1hy', 23, 'Portugal', 'Bachelor', false, false, true,'images/profile/user16.png'),
(17, 'Clara Teixeira', 'clara.t', 'clara.teixeira@gmail.com', '$2b$12$RNKLLl8wcrLjnRR9M0WIye3YCcLwTDl98/tFcA6xNS6pn4UEOEJYa', 24, 'Portugal', 'Master', false, true, false,'images/profile/user17.png'),
(18, 'Pablo Gomez', 'pablo.g', 'pablo.gomez@gmail.com', '$2b$12$dQDxLl/Ud3FL3qyC6xFqueEPx3HZ6nTt5PQ2G/DnOaJw0kvzOpioO', 24, 'Spain', 'PhD', false, false, false,'images/profile/user18.png'),
(19, 'Beatriz Pinto', 'beatriz.p', 'beatriz.pinto@gmail.com', '$2b$12$uTDEbP0hQ4Q9xpi6Xu5f5egPpQpo2BhB/q0g67Y/4xa5KmW3GYd0G', 26, 'Portugal', 'Bachelor', false, false, false,'images/profile/user19.png'),
(20, 'Gabriel Ferreira', 'gabriel.f', 'gabriel.ferreira@gmail.com', '$2b$12$KiQRe9C5d3MCZzMSgbFOs.TmAtCcxu.xW6XSsB76haEs9s.BLJdRy', 22, 'Portugal', 'Master', false, false, false,'images/profile/user20.png'),
(21, 'Tiago Martins', 'tiago.m', 'tiago.martins@gmail.com', '$2b$12$nIa2vgfjhehSX/aUKaEVxeVfg2rYPdDo8Ay1MtC01AwpA2cZXwbPa', 22, 'Portugal', 'Bachelor', false, false, true,'images/profile/user21.png'),
(22, 'Helena Carvalho', 'helena.c', 'helena.carvalho@gmail.com', '$2b$12$igJLwJ5QI91h38gVtwBYR.Sr6I.NXAq5CuBemCi9stTHf./kWeRjK', 28, 'Portugal', 'PhD', false, false, false,'images/profile/user22.png'),
(23, 'Marta Gomes', 'marta.g', 'marta.gomes@gmail.com', '$2b$12$ltA5flYK/PgdHE09GfvWCe8Mea1on5ffonrh.J1K5KEozKNxhcE0O', 25, 'Portugal', 'Master', false, true, false,'images/profile/user23.png'),
(24, 'Gustavo Moreira', 'gustavo.m', 'gustavo.moreira@gmail.com', '$2b$12$UTOf.t32Sb/QZ.ItJS1ci.tQks8NIrHZzcU17DeqxOyRW5y3S9SX6', 24, 'Portugal', 'Bachelor', false, false, false,'images/profile/user24.png'),
(25, 'André Mendonça', 'andre.m', 'andre.mendonca@gmail.com', '$2b$12$.iUbk5QVoTiTt8xbz5ugB.V/K89wbDtHLYBZRe.r4ClaBMcWJp7q.', 26, 'Brazil', 'PhD', true, false, false,'images/profile/user25.png'),
(26, 'Daniela Lopes', 'daniela.l', 'daniela.lopes@gmail.com', '$2b$12$aGJWHDunEN8KYSdMi5s2PefozKB3SbR8XVhN63pybSjPUoGqYuxMW', 27, 'Portugal', 'Bachelor', false, false, true,'images/profile/user26.png'),
(27, 'Mateus Silva', 'mateus.s', 'mateus.silva@gmail.com', '$2b$12$ukuwYCZbk3Ci0AUE0ySbsOv/rSMKw5Zd3/h75k7Zotkwnlq6zCUT2', 23, 'Portugal', 'Master', false, true, false,'images/profile/user27.png'),
(28, 'Fábio Sousa', 'fabio.s', 'fabio.sousa@gmail.com', '$2b$12$PCo5DC3LtYyymcxnfbjUGeh0FmPegb1YexD3iK6nXtmOpgpi7Hx1a', 25, 'Portugal', 'Bachelor', false, false, false,'images/profile/user28.png'),
(29, 'Isabel Ferreira', 'isabel.f', 'isabel.ferreira@gmail.com', '$2b$12$2mUwVwD70LjIoAqT00J02uNzwiKf0FKA4XQrZ7IIXMKeulSnqxmUC', 29, 'Portugal', 'PhD', false, false, false,'images/profile/user29.png'),
(30, 'Diogo Araújo', 'diogo.a', 'diogo.araujo@gmail.com', '$2b$12$gBgRnK35YVUGhvsBlxvd9Onuk9KMyjq7CeF5IuhooMDUqd6sqAGx6', 27, 'Portugal', 'Master', false, true, false,'images/profile/user30.png');

-- Populate Badge Table
INSERT INTO badge (id, title, icon, description)
VALUES 
    (1, 'Expert', 'expert.png', 'For achieving 100 likes across all answers'), -- For achieving 100 likes across all answers
    (2, 'Contributor', 'contributor.png', 'For creating the first question'),        -- For creating the first question
    (3, 'Top Answerer', 'topAnswerer.png', 'For receiving 50 likes on a single answer'),     -- For receiving 50 likes on a single answer
    (4, 'Rising Star', 'risingStar.png', 'For posting 10 answers'),        -- For posting 10 answers
    (5, 'Community Leader', 'communityLeader.png', 'For reaching a total of 50 posts (questions + answers)'); -- For reaching a total of 50 posts (questions + answers)

-- Populate Tag Table
INSERT INTO tag (id, name, color, color_text)
VALUES 
	-- General Topics
    (1, 'Academic Advice', '#FCAD00', '#FFF'),
    (2, 'Coursework Help', '#008080', '#FFF'),
    (3, 'Exam Preparation', '#4C99E0', '#FFF'),
    (4, 'Internships and Careers', '#AF608D', '#FFF'),
    (5, 'Student Life', '#98CB4C', '#FFF'),
    (6, 'Networking', '#A92228', '#FFF'),
    (7, 'Study Resources', '#70223E', '#FFF'),
    (8, 'Programming Help', '#091F98', '#fff'),
    (9, 'Projects and Collaborations', '#61D0BC', '#FFF'),
    (10, 'Mental Health and Wellbeing', '#F6C8DE', '#000'),
    (11, 'Tips and Tricks', '#A051B4', '#FFF'),
    (12, 'Workshops and Events', '#539A51', '#FFF'),
    (13, 'Feedback and Suggestions', '#6C6C8A', '#FFF'),

    -- FEUP Major-Specific Tags
    (14, 'Informatics Engineering', '#5B6FBE', '#FFF'),
    (15, 'Mechanical Engineering', '#EF8D3A', '#FFF'),
    (16, 'Civil Engineering', '#926230', '#FFF'),
    (17, 'Electrical and Computer Engineering', '#4AA4A4', '#FFF'),
    (18, 'Chemical Engineering', '#B53167', '#FFF'),
    (19, 'Industrial Engineering and Management', '#A58489', '#FFF'),
    (20, 'Environmental Engineering', '#EC6338', '#FFF'),
    (21, 'Materials Engineering', '#1C3822', '#FFF'),
    (22, 'Biomedical Engineering', '#5B21A6', '#FFF');

-- Populate Question_Post Table
INSERT INTO question_post (id, text, date, is_edited, user_id, title, tag_id)
VALUES 
    -- Exam Preparation (6 questions)
    (1, 'What are effective methods for preparing for multiple exams in the same week?', '2024-10-01', false, 1, 'Exam Preparation for Multiple Exams', 3),
    (2, 'How do I best organize my notes and materials before finals?', '2024-10-02', false, 2, 'Organizing Notes for Finals', 3),
    (3, 'Does anyone have tips for last-minute revision?', '2024-10-03', false, 3, 'Last-Minute Revision Tips', 3),
    (4, 'What’s the best way to tackle engineering math exams?', '2024-10-04', false, 4, 'Engineering Math Exam Tips', 3),
    (5, 'How can I handle stress during the exam week?', '2024-10-05', false, 5, 'Managing Exam Stress', 3),
    (6, 'What resources are helpful for final exam prep in engineering?', '2024-10-06', false, 6, 'Resources for Engineering Finals', 3),
    
    -- Academic Advice
    (7, 'What are the best courses to take in my second year of engineering?', '2024-10-07', false, 2, 'Recommended Second-Year Courses', 1),
    (8, 'How do I balance coursework with personal projects?', '2024-10-08', false, 3, 'Balancing Coursework and Projects', 1),

    -- Programming Help
    (9, 'Can someone recommend tutorials for learning Python for engineering tasks?', '2024-10-09', false, 4, 'Python Tutorials for Engineers', 8),
    (10, 'What are the best strategies and resources for preparing for internships in software engineering? I would like to know about coding practice, interview preparation, and resume building tips.', '2024-10-10', false, 5, 'Preparing for Internships in Software Engineering', 4),

    -- Internships and Careers
    (11, 'What’s the best way to build a portfolio for software engineering?', '2024-10-11', false, 1, 'Building a Software Engineering Portfolio', 4),
    (12, 'Can anyone share their experience with summer internships in Portugal?', '2024-10-12', false, 2, 'Internship Experiences in Portugal', 4),

    -- Student Life
    (13, 'What are some of the best student organizations to join at FEUP?', '2024-10-13', false, 3, 'Student Organizations at FEUP', 5),
    (14, 'How do you manage living in Porto while studying full-time?', '2024-10-14', false, 4, 'Managing Life in Porto as a Student', 5),

    -- Environmental Engineering
    (15, 'What are the current projects or research areas in environmental engineering?', '2024-10-15', false, 5, 'Research in Environmental Engineering', 20),

    -- Mechanical Engineering
    (16, 'Can anyone suggest good resources for understanding thermodynamics?', '2024-10-16', false, 6, 'Thermodynamics Resources', 15),
    (17, 'How can I prepare for mechanical engineering lab work?', '2024-10-17', false, 1, 'Preparing for Lab Work in Mechanical Engineering', 15),

    -- Civil Engineering
    (18, 'What are some common software tools used in civil engineering?', '2024-10-18', false, 2, 'Software Tools for Civil Engineering', 16),
    (19, 'Can anyone recommend good online courses for structural engineering basics?', '2024-10-19', false, 3, 'Online Courses for Structural Engineering', 16),

    -- Networking
    (20, 'How do I make the most of networking events at FEUP?', '2024-10-20', false, 4, 'Making the Most of Networking Events', 6),

    -- Technical Skills
    (21, 'What are some essential programming languages for engineering students?', '2024-10-21', false, 5, 'Essential Programming Languages', 12),
    (22, 'What technical skills should I focus on for a career in tech?', '2024-10-22', false, 6, 'Technical Skills for a Career in Tech', 12),

    -- Chemical Engineering
    (23, 'What are good ways to prepare for organic chemistry exams?', '2024-10-23', false, 1, 'Preparing for Organic Chemistry Exams', 18),
    (24, 'Any advice on choosing electives for chemical engineering?', '2024-10-24', false, 2, 'Electives for Chemical Engineering', 18),

    -- Biomedical Engineering
    (25, 'What are useful resources for learning about biomedical devices?', '2024-10-25', false, 3, 'Resources for Biomedical Devices', 22),

    -- Feedback and Suggestions
    (26, 'How can the platform improve in terms of user experience?', '2024-10-26', false, 4, 'Suggestions for Platform Improvement', 13),
    (27, 'What features would you like to see added to askFEUP?', '2024-10-27', false, 5, 'Feature Suggestions for askFEUP', 13),

    -- Tips and Tricks
    (28, 'What are some hidden features in Moodle that students should know?', '2024-10-28', false, 6, 'Hidden Features in Moodle', 11),
    (29, 'Does anyone have study hacks for heavy course loads?', '2024-10-29', false, 1, 'Study Hacks for Busy Students', 11),

    -- Informatics Engineering
    (30, 'Where can I find good tutorials for learning Swift?', '2024-10-30', false, 2, 'Swift Tutorials for Beginners', 14),

    -- Mental Health and Wellbeing
    (31, 'How can I manage study anxiety during finals week?', '2024-10-01', false, 7, 'Managing Study Anxiety', 10),
    (32, 'What are the best practices for balancing academics and mental health?', '2024-09-02', false, 8, 'Balancing Academics and Mental Health', 10),
    (33, 'Are there any mindfulness techniques students can use daily?', '2024-11-03', false, 9, 'Mindfulness Techniques for Students', 10),
    (34, 'How do I identify early signs of burnout while studying?', '2024-12-04', false, 10, 'Identifying Burnout as a Student', 10),
    (35, 'What are some affordable mental health resources for students?', '2024-08-05', false, 11, 'Affordable Mental Health Resources', 10),

    -- Networking
    (36, 'What are some tips for building a strong LinkedIn profile as a student?', '2024-12-06', false, 12, 'Building a Strong LinkedIn Profile', 6),
    (37, 'How can I effectively network during industry events?', '2024-11-07', false, 13, 'Effective Networking Strategies', 6),
    (38, 'What are good icebreaker questions for networking events?', '2024-10-08', false, 14, 'Icebreaker Questions for Networking', 6),

    -- Environmental Engineering
    (39, 'What are the latest advancements in wastewater treatment technologies?', '2024-12-15', false, 7, 'Advancements in Wastewater Treatment', 20),
    (40, 'How can we improve urban sustainability through green infrastructure?', '2024-09-19', false, 8, 'Improving Urban Sustainability', 20),

    -- Materials Engineering
    (41, 'What are the key properties to consider when selecting materials for aerospace applications?', '2024-11-12', false, 1, 'Material Selection for Aerospace', 21),
    (42, 'How can materials engineers contribute to sustainable manufacturing practices?', '2024-11-13', false, 2, 'Sustainable Manufacturing in Materials Engineering', 21),

    -- Industrial Engineering and Management
    (43, 'What are the key principles of lean manufacturing?', '2024-11-24', false, 13, 'Understanding Lean Manufacturing', 19),
    (44, 'How can data analytics improve supply chain management in industries?', '2024-11-25', false, 14, 'Data Analytics in Supply Chain Management', 19);

-- Populate Answer_Post Table
INSERT INTO answer_post (id, text, date, is_edited, user_id, question_id, is_correct)
VALUES 
    -- Answers for question_id 1 (Exam Preparation in Engineering)
    (1, 'For exam preparation in engineering, I recommend starting early and focusing on key topics discussed in class.', '2024-10-02', false, 30, 1, true),
    (2, 'Practice past papers and focus on problem-solving exercises. It helps a lot!', '2024-10-02', false, 2, 1, false),
    (3, 'Organize a study group with classmates to go over challenging topics together.', '2024-10-03', false, 3, 1, false),
    (4, 'Use resources like textbooks and online videos to clarify tough concepts.', '2024-10-03', false, 4, 1, false),
    (5, 'Take regular breaks and review your notes systematically. It reduces stress.', '2024-10-04', false, 5, 1, false),

    -- Answers for question_id 10 (Preparing for Internships in Software Engineering)
    (6, 'For internship applications, be prepared for technical and behavioral interviews.', '2024-10-11', false, 6, 10, false),
    (7, 'Networking is crucial—attend events and reach out to alumni for advice.', '2024-10-11', false, 7, 10, false),
    (8, 'Highlight your projects on GitHub and keep your LinkedIn updated.', '2024-10-12', false, 8, 10, false),
    (9, 'Research the companies you apply to and tailor your resume to each one.', '2024-10-13', false, 9, 10, true),
    (10, 'Join study groups to prepare for coding challenges. Practice is key!', '2024-10-14', false, 10, 10, false),

    -- Answers for question_id 21 (Resources for Learning Swift)
    (11, 'Apple Developer offers free resources that are great for beginners in Swift.', '2024-10-15', false, 11, 30, false), 
    (12, 'I recommend Hacking with Swift by Paul Hudson for structured learning.', '2024-10-15', false, 12, 30, false),
    (13, 'Swift Playgrounds is a fantastic app to start with, especially for interactive learning.', '2024-10-16', false, 13, 30, true),
    (14, 'Check out Udacity’s Swift course; it’s very beginner-friendly.', '2024-10-16', false, 14, 30, false),
    (15, 'Use GitHub to explore Swift projects, which can help deepen your understanding.', '2024-10-17', false, 15, 30, false),

    -- Additional varied answers for other questions
    (16, 'Join workshops and ask professors about research opportunities.', '2024-10-18', false, 16, 18, false),
    (17, 'Use Trello or Notion for organizing group projects efficiently.', '2024-10-18', false, 17, 17, false),
    (18, 'Plan your day by setting time aside for both study and breaks.', '2024-10-19', false, 18, 16, false),
    (19, 'LeetCode is perfect for practice; try solving a few problems daily.', '2024-10-20', false, 19, 19, false),
    (20, 'Getting the basics of data structures can be helpful for software engineering.', '2024-10-21', false, 4, 19, true),
    (21, 'For aerospace, prioritize materials with high strength-to-weight ratios, like titanium alloys.', '2024-11-14', false, 2, 41, true),
    (22, 'Carbon composites are also great for lightweight and durable aerospace structures.', '2024-11-15', false, 4, 41, false),
    (23, 'Materials engineers can reduce waste by implementing closed-loop recycling systems.', '2024-11-16', false, 5, 42, false),
    (24, 'Focus on developing biodegradable or recyclable materials for industrial use.', '2024-11-17', false, 6, 42, true),
    
    (25, 'Nanotechnology is transforming wastewater treatment by enabling efficient contaminant removal.', '2024-11-20', false, 9, 38, true),
    (26, 'Electrocoagulation is gaining popularity as an eco-friendly wastewater treatment method.', '2024-11-21', false, 10, 38, false),

    (27, 'Green roofs and permeable pavements are effective solutions for urban sustainability.', '2024-12-15', false, 11, 39, true),
    (28, 'Incorporate urban forests to combat air pollution and reduce heat islands.', '2024-12-15', false, 12, 39, false),

    (39, 'Lean manufacturing emphasizes waste reduction, continuous improvement, and value delivery.', '2024-11-26', false, 15, 43, true),
    (40, 'Key tools include value stream mapping, 5S, and Kaizen for process improvement.', '2024-11-27', false, 16, 43, false),

    (41, 'Data analytics helps predict demand patterns and optimize inventory levels.', '2024-11-28', false, 17, 44, true),
    (42, 'Use machine learning algorithms to forecast supply chain disruptions.', '2024-11-29', false, 18, 44, false),

    (43, 'Common signs include constant fatigue despite getting enough sleep, difficulty concentrating or retaining information, and a lack of motivation to engage with your studies. You may also feel emotionally overwhelmed, irritable, or disconnected from tasks that you usually find enjoyable. Physical symptoms like headaches, muscle tension, and sleep disturbances are also common. Behavioral changes such as procrastination, avoiding responsibilities, or withdrawing from social interactions are key indicators as well.', '2024-12-16', false, 4, 34, false),
    (44, 'Focus on key resources like lecture notes, textbooks, and past exam papers to practice problem-solving. Online platforms like Khan Academy, MIT OpenCourseWare, or YouTube tutorials can help clarify challenging concepts. Study groups are valuable for discussing topics and learning different approaches, while forums like Stack Exchange offer quick help for specific problems. Manage your time effectively and ensure you get enough rest to stay sharp during preparation.', '2024-12-16', false, 4, 6, false),
    (45, 'Use a professional photo, write a clear headline, and highlight your skills, education, and projects. Add keywords, connect with peers, and follow industry leaders to grow your network.', '2024-12-15', false, 4, 36, false),
    (46, 'Moodle offers hidden gems like activity completion tracking, calendar integration for deadlines, and the ability to download course materials in bulk. Use forums to engage with peers and the gradebook to monitor your progress.', '2024-12-15', false, 4, 28, false),
    (47, 'Focus on understanding formulas and their applications, practice past papers to identify patterns, and break problems into smaller steps. Time management is key—start with easier questions to build confidence.', '2024-12-13', false, 13, 4, false),
    (48, 'The best optional courses for second year are Python or Excel. They’re highly practical and boost problem-solving skills.', '2024-11-30', false, 4, 7, false),
    (49, 'Balance academics and mental health by setting realistic goals, taking regular breaks, and staying active. Prioritize sleep, healthy eating, and hobbies to recharge. Seek support from friends or counselors if overwhelmed, and don’t overcommit.', '2024-11-28', false, 4, 32, false),
    (50, 'Data analytics enhances supply chain management by optimizing inventory, predicting demand, reducing costs, and improving delivery efficiency. It helps identify inefficiencies and ensures better planning and customer satisfaction.', '2024-11-28', false, 4, 44, false),

    (51, 'I recommend starting with "Python for Everybody" on Coursera. It’s beginner-friendly and covers essential concepts for engineers.', '2024-12-16', false, 27, 9, false),
    (52, 'Check out Real Python tutorials. They have hands-on guides tailored for data analysis and engineering use cases.', '2024-12-15', false, 14, 9, false),
    (53, 'YouTube channels like Corey Schafer and Tech With Tim have excellent Python tutorials for engineers.', '2024-12-20', false, 9, 9, false);

-- Populate Comment_Post Table
INSERT INTO comment_post (id, text, date, is_edited, user_id, answer_id)
VALUES 
    (1, 'Great advice! Starting early really makes a difference.', '2024-10-02', FALSE, 20, 1),
    (2, 'Problem-solving practice is definitely a must for exams!', '2024-10-03', FALSE, 19, 2),
    (3, 'Study groups can be super helpful. Thanks for the tip!', '2024-10-03', FALSE, 18, 3),
    
    (4, 'Textbooks sometimes offer in-depth explanations. Good suggestion!', '2024-10-04', FALSE, 17, 4),
    (5, 'Breaks are so important for staying focused!', '2024-10-04', FALSE, 16, 5),
    
    (6, 'Thanks! Technical prep is really helpful for internships.', '2024-10-11', FALSE, 15, 6),
    (7, 'Networking has made a difference in my career path. Great point!', '2024-10-12', FALSE, 14, 7),
    (8, 'Keeping LinkedIn updated is so important. Good reminder!', '2024-10-13', FALSE, 13, 8),
    
    (9, 'Tailoring the resume for each company works well!', '2024-10-14', FALSE, 12, 9),
    (10, 'Coding practice with friends is really motivating!', '2024-10-14', FALSE, 11, 10),

    -- Comments for Answer 1
    (11, 'Apple Developer is a great resource, I agree!', '2024-10-15', FALSE, 10, 1),
    (12, 'Hacking with Swift is an excellent book, highly recommended.', '2024-10-16', FALSE, 9, 1),
    (13, 'Swift Playgrounds makes learning so interactive!', '2024-10-16', FALSE, 8, 1),

    -- Comments for Answer 2
    (14, 'Udacity courses are well-structured. Good suggestion!', '2024-10-17', FALSE, 7, 2),
    (15, 'Browsing GitHub projects really helps with learning!', '2024-10-17', FALSE, 6, 2),
    (16, 'Workshops can open up so many opportunities.', '2024-10-18', FALSE, 5, 2),

    (17, 'Trello and Notion are my go-to tools for projects.', '2024-10-19', FALSE, 4, 17),
    (18, 'Planning time effectively is such a useful habit!', '2024-10-19', FALSE, 3, 18),
    (19, 'LeetCode has really helped me with coding challenges.', '2024-10-20', FALSE, 2, 19),
    (20, 'Agree, knowing data structures is essential.', '2024-10-21', FALSE, 1, 20);

-- Populate Question_Like Table
INSERT INTO question_like (user_id, post_id, date)
VALUES
    (1, 2, '2024-10-31'),
    (2, 1, '2024-11-17'), 
    (3, 1, '2024-11-10'),  
    (1, 16, '2024-11-05'),  
    (4, 2, '2024-12-06'), 
    (5, 2, '2024-12-07'),  
    (1, 3, '2024-12-08'),  
    (2, 3, '2024-12-09'),  
    (3, 4, '2024-12-10'),  
    (3, 5, '2024-11-20'), 
    (5, 4, '2024-11-21'),  
    (1, 5, '2024-11-22'),  
    (2, 5, '2024-11-23'),  
    (3, 6, '2024-11-24'),  
    (4, 6, '2024-11-25'),  
    (5, 6, '2024-11-26'),  
    (1, 7, '2024-11-27'),  
    (2, 8, '2024-11-28'),  
    (4, 8, '2024-11-29'),  
    (6, 9, '2024-11-30'),  
    (5, 9, '2024-12-11'),  
    (1, 10, '2024-12-12'), 
    (2, 10, '2024-12-13'),
    (3, 11, '2024-12-14'), 
    (4, 11, '2024-12-15'), 
    (5, 12, '2024-12-16'), 
    (1, 12, '2024-12-17'), 
    (2, 13, '2024-12-18'), 
    (3, 14, '2024-12-19'), 
    (4, 15, '2024-12-20'),
    (10, 15, '2024-11-11'), 
    (5, 16, '2024-12-12'),
    (6, 1, '2024-11-11'), 
    (7, 1, '2024-12-14'), 
    (8, 2, '2024-11-20'), 
    (9, 2, '2024-12-21'), 
    (6, 3, '2024-12-21'), 
    (7, 4, '2024-12-01'), 
    (8, 4, '2024-12-02'), 
    (9, 5, '2024-12-03'), 
    (10, 5, '2024-12-04'), 
    (6, 10, '2024-12-05'), 
    (7, 15, '2024-12-06'), 
    (8, 7, '2024-12-07'), 
    (9, 8, '2024-12-08'), 
    (10, 8, '2024-12-09'), 
    (6, 26, '2024-12-10'), 
    (7, 9, '2024-12-11'), 
    (8, 10, '2024-12-12'), 
    (9, 10, '2024-12-13'), 
    (10, 11, '2024-12-14'), 
    (7, 11, '2024-12-15'), 
    (8, 12, '2024-12-16'), 
    (9, 12, '2024-12-17'), 
    (10, 13, '2024-12-18'), 
    (6, 13, '2024-12-19'), 
    (7, 14, '2024-12-20'), 
    (8, 14, '2024-12-21'), 
    (9, 15, '2024-11-20'), 
    (10, 16, '2024-11-21'), 
    (7, 16, '2024-11-22'), 
    (8, 17, '2024-11-23'), 
    (9, 17, '2024-11-24'), 
    (10, 17, '2024-11-25'), 
    (6, 18, '2024-11-26'), 
    (7, 18, '2024-11-27'), 
    (8, 19, '2024-11-28'), 
    (9, 19, '2024-11-29'), 
    (10, 19, '2024-11-30'), 
    (6, 20, '2024-11-29'), 
    (7, 20, '2024-11-28'), 
    (8, 21, '2024-11-27'), 
    (9, 21, '2024-11-30'), 
    (10, 21, '2024-11-29'), 
    (6, 19, '2024-11-28'), 
    (7, 22, '2024-11-27'), 
    (8, 23, '2024-11-26'), 
    (9, 23, '2024-11-25'), 
    (10, 24, '2024-11-24'), 
    (6, 24, '2024-11-23'), 
    (7, 25, '2024-11-22'), 
    (8, 25, '2024-11-21'), 
    (9, 25, '2024-11-20'), 
    (10, 26, '2024-11-19'), 
    (7, 26, '2024-11-18'), 
    (8, 27, '2024-11-17'), 
    (9, 27, '2024-11-16'), 
    (10, 28, '2024-11-15'), 
    (6, 30, '2024-11-14'), 
    (7, 29, '2024-11-13'), 
    (8, 29, '2024-11-12'), 
    (9, 30, '2024-11-11'), 
    (10, 30, '2024-11-10');

-- Populate Answer_Like Table
INSERT INTO answer_like (user_id, post_id)
VALUES 
    (2, 1),  
    (3, 1),  
    (1, 2),  
    (4, 2),  
    (5, 2), 
    (1, 3),  
    (2, 3),  
    (5, 4),  
    (1, 5), 
    (2, 5),  
    (3, 6),  
    (4, 6),  
    (5, 6),  
    (1, 7),  
    (2, 8),  
    (3, 8),  
    (4, 9), 
    (5, 9),  
    (1, 10), 
    (2, 10), 
    (3, 11), 
    (4, 11),
    (5, 12), 
    (1, 12),
    (2, 13), 
    (3, 14), 
    (4, 15), 
    (5, 16); 

-- Populate Comment_Like Table
INSERT INTO comment_like (user_id, post_id)
VALUES 
    (2, 1),  
    (3, 1),  
    (4, 1),  
    (1, 2), 
    (5, 2), 
    (2, 4),  
    (4, 5),  
    (5, 6),  
    (1, 7),  
    (2, 8),  
    (3, 9),  
    (1, 10), 
    (4, 11), 
    (5, 12), 
    (3, 13), 
    (2, 14), 
    (4, 15), 
    (1, 16), 
    (5, 17), 
    (30, 18), 
    (20, 19), 
    (4, 20); 

-- Populate User_Follow_Tag Table
INSERT INTO user_follow_tag (user_id, tag_id)
VALUES 
    (1, 1),  -- João follows Tag 1
    (1, 2),  -- João follows Tag 2
    (1, 3),  -- João follows Tag 3
    (1, 20),  -- João follows Tag 3
    (2, 1),  -- Mariana follows Tag 1
    (2, 3),  -- Mariana follows Tag 3
    (2, 7),  -- Mariana follows Tag 3
    (3, 2),  -- Francisco follows Tag 2
    (4, 1),  -- Inês follows Tag 1
    (4, 2),  -- Inês follows Tag 2
    (4, 3),  -- Inês follows Tag 3
    (4, 10),  -- Inês follows Tag 10
    (4, 13),  -- Inês follows Tag 13
    (4, 14),  -- Inês follows Tag 14
    (5, 2),  -- Rui follows Tag 2
    (5, 3),  -- Rui follows Tag 3
    (6, 3),  -- Carla follows Tag 3
    (6, 22),  -- Carla follows Tag 22
    (7, 1),  -- André follows Tag 1
    (8, 4),  -- Ana follows Tag 4
    (9, 1),  -- Tomás follows Tag 1
    (9, 2),  -- Tomás follows Tag 2
    (9, 3),  -- Tomás follows Tag 3
    (10, 5), -- Sofia follows Tag 5
    (11, 4), -- Luís follows Tag 4
    (12, 2), -- Sara follows Tag 2
    (12, 3), -- Sara follows Tag 3
    (13, 1), -- Miguel follows Tag 1
    (13, 2), -- Miguel follows Tag 2
    (13, 8), -- Miguel follows Tag 8
    (14, 6), -- Raquel follows Tag 6
    (14, 16), -- Raquel follows Tag 16
    (14, 17), -- Raquel follows Tag 17
    (15, 2), -- Vasco follows Tag 2
    (15, 5), -- Vasco follows Tag 5
    (16, 7), -- Ricardo follows Tag 7
    (17, 1), -- Clara follows Tag 1
    (17, 2), -- Clara follows Tag 2
    (17, 9), -- Clara follows Tag 9
    (17, 20), -- Clara follows Tag 20
    (18, 5), -- Pablo follows Tag 5
    (18, 19), -- Pablo follows Tag 19
    (18, 21), -- Pablo follows Tag 21
    (18, 11), -- Pablo follows Tag 11
    (19, 3), -- Beatriz follows Tag 3
    (20, 1), -- Gabriel follows Tag 1
    (20, 2), -- Gabriel follows Tag 2
    (20, 4), -- Gabriel follows Tag 4
    (21, 6), -- Tiago follows Tag 6
    (21, 10), -- Tiago follows Tag 10
    (22, 3), -- Helena follows Tag 3
    (23, 5), -- Marta follows Tag 5
    (23, 10), -- Marta follows Tag 10
    (24, 4), -- Gustavo follows Tag 4
    (25, 1), -- Lucas follows Tag 1
    (26, 2), -- Daniela follows Tag 2
    (27, 3), -- Mateus follows Tag 3
    (27, 13), -- Mateus follows Tag 13
    (27, 16), -- Mateus follows Tag 16
    (28, 13), -- Fábio follows Tag 13
    (28, 14), -- Fábio follows Tag 14
    (29, 11), -- Isabel follows Tag 11
    (29, 18), -- Isabel follows Tag 18
    (30, 2), -- Diogo follows Tag 2
    (30, 22); -- Diogo follows Tag 22

-- Populate User_Follow_Question Table
INSERT INTO user_follow_question (user_id, question_id)
VALUES
    -- Exam Preparation Questions
    (2, 1),  -- User 2 follows Question 1 (user_id 1 cannot follow their own question)
    (3, 1),
    (4, 2),  -- User 4 follows Question 2 (user_id 2 cannot follow their own question)
    (5, 2),
    (1, 3),  -- User 1 follows Question 3 (user_id 3 cannot follow their own question)
    (6, 3),
    (1, 4),  -- User 1 follows Question 4 (user_id 4 cannot follow their own question)
    (3, 5),  -- User 3 follows Question 5 (user_id 5 cannot follow their own question)
    (2, 5),
    (4, 6),  -- User 4 follows Question 6 (user_id 6 cannot follow their own question)

    -- Academic Advice
    (1, 7),  -- User 1 follows Question 7 (user_id 2 cannot follow their own question)
    (3, 7),
    (2, 8),  -- User 2 follows Question 8 (user_id 3 cannot follow their own question)
    (4, 8),

    -- Programming Help
    (5, 9),  -- User 5 follows Question 9 (user_id 4 cannot follow their own question)
    (6, 9),
    (3, 10), -- User 3 follows Question 10 (user_id 5 cannot follow their own question)
    (4, 10),

    -- Internships and Careers
    (2, 11), -- User 2 follows Question 11 (user_id 1 cannot follow their own question)
    (3, 11),
    (1, 12), -- User 1 follows Question 12 (user_id 2 cannot follow their own question)
    (4, 12),

    -- Student Life
    (5, 13), -- User 5 follows Question 13 (user_id 3 cannot follow their own question)
    (6, 13),
    (2, 14), -- User 2 follows Question 14 (user_id 4 cannot follow their own question)
    (3, 14),

    -- Environmental Engineering
    (6, 15), -- User 6 follows Question 15 (user_id 5 cannot follow their own question)
    (3, 15),
    (4, 39), -- User 4 follows Question 39 (user_id 7 cannot follow their own question)
    (5, 39),
    (3, 40), -- User 3 follows Question 40 (user_id 8 cannot follow their own question)
    (6, 40),

    -- Materials Engineering
    (5, 41), -- User 5 follows Question 41 (user_id 1 cannot follow their own question)
    (6, 41),
    (3, 42), -- User 3 follows Question 42 (user_id 2 cannot follow their own question)
    (4, 42),

    -- Industrial Engineering and Management
    (6, 43), -- User 6 follows Question 43 (user_id 13 cannot follow their own question)
    (2, 43),
    (4, 44), -- User 4 follows Question 44 (user_id 14 cannot follow their own question)
    (3, 44),

    -- Mental Health and Wellbeing
    (2, 31), -- User 2 follows Question 31 (user_id 7 cannot follow their own question)
    (3, 31),
    (4, 32), -- User 4 follows Question 32 (user_id 8 cannot follow their own question)
    (6, 32),
    (5, 33), -- User 5 follows Question 33 (user_id 9 cannot follow their own question)
    (1, 33),
    (4, 34), -- User 4 follows Question 34 (user_id 10 cannot follow their own question)
    (6, 34),

    -- Networking
    (5, 36), -- User 5 follows Question 36 (user_id 12 cannot follow their own question)
    (2, 36),
    (3, 37), -- User 3 follows Question 37 (user_id 13 cannot follow their own question)
    (6, 37),
    (4, 38), -- User 4 follows Question 38 (user_id 14 cannot follow their own question)
    (5, 38);

-- Populate Post_Report_Reason Table
INSERT INTO post_report_reason (id, reason)
VALUES
    (1, 'Spam'),
    (2, 'Hate'),
    (3, 'Improper Content');

-- Populate Post_Report Table
INSERT INTO post_report (id, user_id, post_type, post_id, reason_id)
VALUES
    (1, 16, 'question', 10, 1),
    (2, 20, 'answer', 1, 3);

SELECT setval('user_id_seq', (SELECT MAX(id) FROM "user"));
SELECT setval('badge_id_seq', (SELECT MAX(id) FROM badge));
SELECT setval('tag_id_seq', (SELECT MAX(id) FROM tag));
SELECT setval('question_post_id_seq', (SELECT MAX(id) FROM question_post));
SELECT setval('answer_post_id_seq', (SELECT MAX(id) FROM answer_post));
SELECT setval('comment_post_id_seq', (SELECT MAX(id) FROM comment_post));
SELECT setval('post_report_id_seq', (SELECT MAX(id) FROM post_report));
SELECT setval('post_report_reason_id_seq', (SELECT MAX(id) FROM post_report_reason));

SET app.current_user_id TO '0';

