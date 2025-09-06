import React from 'react';
import {
  BaseEdge,
  EdgeLabelRenderer,
  EdgeProps,
  getStraightPath,
} from 'reactflow';

/**
 * Simple relationship edge that shows relationship type and direction
 */
const RelationshipLine: React.FC<EdgeProps> = ({
  sourceX,
  sourceY,
  targetX,
  targetY,
  sourcePosition,
  targetPosition,
  style = {},
  data,
  label,
}) => {
  const [edgePath, labelX, labelY] = getStraightPath({
    sourceX,
    sourceY,
    targetX,
    targetY,
  });

  // Get relationship info from data
  const relationships = data?.relationships || [];
  const relationshipCount = data?.relationshipCount || 1;

  // Determine edge style based on relationship type
  const getEdgeStyle = () => {
    if (relationships.some((r: any) => r.relationshipType === 'belongsToMany')) {
      return { stroke: '#8b5cf6', strokeWidth: 3 }; // Purple, thick for many-to-many
    } else if (relationships.some((r: any) => r.relationshipType === 'hasMany')) {
      return { stroke: '#3b82f6', strokeWidth: 2 }; // Blue for one-to-many
    } else if (relationships.some((r: any) => r.relationshipType === 'hasOne')) {
      return { stroke: '#10b981', strokeWidth: 2 }; // Green for one-to-one
    } else {
      return { stroke: '#6b7280', strokeWidth: 2 }; // Gray default
    }
  };

  return (
    <>
      <BaseEdge
        path={edgePath}
        style={{
          ...style,
          ...getEdgeStyle(),
        }}
      />
      <EdgeLabelRenderer>
        <div
          style={{
            position: 'absolute',
            transform: `translate(-50%, -50%) translate(${labelX}px,${labelY}px)`,
            fontSize: 10,
            fontWeight: 'bold',
            pointerEvents: 'all',
            background: 'white',
            border: '1px solid #ddd',
            borderRadius: '4px',
            padding: '2px 6px',
          }}
        >
          {label}
          {relationshipCount > 1 && (
            <div style={{ fontSize: 8, color: '#666' }}>
              ({relationshipCount} rels)
            </div>
          )}
        </div>
      </EdgeLabelRenderer>
    </>
  );
};

export default RelationshipLine;