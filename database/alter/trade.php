ALTER TABLE trade_proposals
ADD INDEX idx_trade_proposals_season_status (
    season_id,
    status
);

ALTER TABLE trade_players
ADD INDEX idx_trade_players_proposal (
    trade_proposal_id
);

ALTER TABLE trade_players
ADD INDEX idx_trade_players_player (
    player_id
);

ALTER TABLE trade_logs
ADD INDEX idx_trade_logs_proposal (
    trade_proposal_id
);

ALTER TABLE trade_players
ADD CONSTRAINT fk_trade_players_proposal
FOREIGN KEY (trade_proposal_id)
REFERENCES trade_proposals(id)
ON DELETE CASCADE;