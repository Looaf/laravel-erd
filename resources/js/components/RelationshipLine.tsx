import React from 'react';
import {
  BaseEdge,
  EdgeLabelRenderer,
  EdgeProps,
  getBezierPath,
} from 'reactflow';

interface RelationshipLineProps extends EdgeProps {
  data?: {
    relationshipType: string;
    sourceKey: string;
    foreignKey: string;
  };
}

const RelationshipLine: React.FC<RelationshipLineProps> = ({
  id,
  sourceX,
  sourceY,
  targetX,
  targetY,
  sourcePosition,
  targetPosition,
  style = {},
  data,
  markerEnd,
}) => {
  const [edgePath, labelX, labelY] = getBezierPath({
    sourceX,
    sourceY,
    sourcePosition,
    targetX,
    targetY,
    targetPosition,
  });

  // Get relationship type styling
  const getRelationshipStyle = (type: string) => {
    const styles = {
      hasOne: {
        stroke: '#10b981', // green
        strokeWidth: 2,
        strokeDasharray: 'none',
      },
      hasMany: {
        stroke: '#3b82f6', // blue
        strokeWidth: 2,
        strokeDasharray: 'none',
      },
      belongsTo: {
        stroke: '#f59e0b', // amber
        strokeWidth: 2,
        strokeDasharray: '5,5',
      },
      belongsToMany: {
        stroke: '#8b5cf6', // violet
        strokeWidth: 3,
        strokeDasharray: 'none',
      },
      morphTo: {
        stroke: '#ef4444', // red
        strokeWidth: 2,
        strokeDasharray: '10,5',
      },
    };

    return styles[type as keyof typeof styles] || {
      stroke: '#6b7280',
      strokeWidth: 2,
      strokeDasharray: 'none',
    };
  };

  // Get relationship type label and icon
  const getRelationshipLabel = (type: string) => {
    const labels = {
      hasOne: { text: '1:1', icon: '→' },
      hasMany: { text: '1:N', icon: '→→' },
      belongsTo: { text: 'N:1', icon: '←' },
      belongsToMany: { text: 'N:N', icon: '↔' },
      morphTo: { text: 'morph', icon: '~' },
    };

    return labels[type as keyof typeof labels] || { text: type, icon: '—' };
  };

  const relationshipType = data?.relationshipType || 'unknown';
  const relationshipStyle = getRelationshipStyle(relationshipType);
  const relationshipLabel = getRelationshipLabel(relationshipType);

  return (
    <>
      <BaseEdge
        path={edgePath}
        markerEnd={markerEnd}
        style={{
          ...style,
          ...relationshipStyle,
        }}
      />
      <EdgeLabelRenderer>
        <div
          style={{
            position: 'absolute',
            transform: `translate(-50%, -50%) translate(${labelX}px,${labelY}px)`,
            fontSize: 11,
            fontWeight: 500,
            pointerEvents: 'all',
          }}
          className="nodrag nopan bg-white border border-gray-300 rounded px-2 py-1 shadow-sm"
        >
          <div className="flex items-center space-x-1">
            <span className="text-gray-600">{relationshipLabel.icon}</span>
            <span className="text-gray-800">{relationshipLabel.text}</span>
          </div>
          {data?.sourceKey && data?.foreignKey && (
            <div className="text-xs text-gray-500 mt-1">
              {data.sourceKey} → {data.foreignKey}
            </div>
          )}
        </div>
      </EdgeLabelRenderer>
    </>
  );
};

export default RelationshipLine;